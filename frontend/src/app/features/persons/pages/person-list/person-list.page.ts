import { ChangeDetectionStrategy, Component, computed, inject, signal, DestroyRef } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject, of, timer } from 'rxjs';
import { catchError, debounceTime, distinctUntilChanged, switchMap, tap, takeWhile } from 'rxjs/operators';
import { ButtonModule } from 'primeng/button';
import { CardModule } from 'primeng/card';
import { CheckboxModule } from 'primeng/checkbox';
import { InputTextModule } from 'primeng/inputtext';
import { ProgressBarModule } from 'primeng/progressbar';
import { SelectModule } from 'primeng/select';
import { TableModule } from 'primeng/table';
import { TagModule } from 'primeng/tag';
import { TooltipModule } from 'primeng/tooltip';

import { Paginated } from '../../../../core/api/api.types';
import { NotificationService } from '../../../../core/notifications/notification.service';
import { ConfirmService } from '../../../../shared/components/confirm-dialog/confirm.service';
import { EmptyStateComponent } from '../../../../shared/components/empty-state/empty-state';
import { IconComponent, IconName } from '../../../../shared/components/icon/icon';
import { PaginationComponent } from '../../../../shared/components/pagination/pagination';
import { SpinnerComponent } from '../../../../shared/components/spinner/spinner';
import {
  DEFAULT_PERSON_FILTERS,
  Person,
  PersonFilters,
  PersonMetadata,
} from '../../models/person.model';
import { PersonService } from '../../services/person.service';

import { PersonStore } from '../../store/person.store';
@Component({
  selector: 'app-person-list',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    FormsModule,
    RouterLink,
    IconComponent,
    PaginationComponent,
    SpinnerComponent,
    EmptyStateComponent,
    ButtonModule,
    CardModule,
    CheckboxModule,
    InputTextModule,
    ProgressBarModule,
    SelectModule,
    TableModule,
    TagModule,
    TooltipModule,
  ],
  templateUrl: './person-list.page.html',
  styleUrl: './person-list.page.scss',
})
export class PersonListPage {
  private readonly persons = inject(PersonService);
  private readonly confirm = inject(ConfirmService);
  private readonly notifications = inject(NotificationService);

  protected readonly store = inject(PersonStore);
  protected readonly items = this.store.items;
  protected readonly filters = this.store.filters;
  protected readonly loading = this.store.loading;
  protected readonly meta = this.store.meta;
  protected readonly metadata = this.store.metadata;
  protected readonly hasFilters = this.store.hasFilters;

  protected readonly documentTypeOptions = computed(() => [
    { value: '', label: 'Todos' },
    ...(this.metadata()?.document_types ?? []),
  ]);
  protected readonly areaOptions = computed(() => [
    { value: '', label: 'Todas' },
    ...(this.metadata()?.areas ?? []),
  ]);
  protected readonly contractTypeOptions = computed(() => [
    { value: '', label: 'Todos' },
    ...(this.metadata()?.contract_types ?? []),
  ]);
  protected readonly statusOptions = [
    { value: '', label: 'Todos' },
    { value: '1', label: 'Activas' },
    { value: '0', label: 'Inactivas' },
  ];
  protected readonly perPageOptions = [15, 25, 50, 100];

  /** Texto del buscador, con retardo para no lanzar una petición por tecla. */
  private readonly searchTerm = new Subject<string>();

  constructor() {
    this.searchTerm
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed())
      .subscribe((search) => this.store.patchFilters({ search }));
  }

  protected onSearch(value: string): void {
    this.searchTerm.next(value);
  }

  /** Aplica cambios de filtro y vuelve siempre a la primera página. */
  protected patchFilters(patch: Partial<PersonFilters>): void {
    this.store.patchFilters(patch);
  }

  protected goToPage(page: number): void {
    this.store.goToPage(page);
  }

  protected sortBy(column: string): void {
    this.store.sortBy(column);
  }

  /** Flecha de orden de la columna, o `null` si no es la que ordena. */
  protected sortIcon(column: string): IconName | null {
    const { sort, direction } = this.store.filters();

    if (sort !== column) {
      return null;
    }

    return direction === 'asc' ? 'chevron-up' : 'chevron-down';
  }

  protected resetFilters(): void {
    this.store.resetFilters();
  }

  /** Devuelve las iniciales del nombre y apellido. */
  protected getInitials(person: Person): string {
    const f = person.first_name?.charAt(0)?.toUpperCase() ?? '';
    const l = person.last_name?.charAt(0)?.toUpperCase() ?? '';
    return f + l || '?';
  }

  protected async remove(person: Person): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Eliminar persona',
      message: `¿Seguro que quieres eliminar a ${person.full_name}? Podrás restaurarla más adelante.`,
      confirmLabel: 'Eliminar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.persons.remove(person.id).subscribe({
      next: () => {
        this.notifications.success(`${person.full_name} se eliminó correctamente.`);
        this.store.reload();
      },
    });
  }

  protected restore(person: Person): void {
    this.persons.restore(person.id).subscribe({
      next: () => {
        this.notifications.success(`${person.full_name} se restauró correctamente.`);
        this.store.reload();
      },
    });
  }

  protected readonly exportingExcel = signal(false);
  protected readonly exportingPdf = signal(false);

  // ---------------------------------------------------------------- Importación
  protected readonly importing = signal(false);
  protected readonly importProgress = signal(0);
  protected readonly importTotal = signal(0);
  private readonly destroyRef = inject(DestroyRef);

  protected triggerFileInput(fileInput: HTMLInputElement): void {
    fileInput.click();
  }

  protected onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const file = input.files[0];
    input.value = '';
    
    this.importing.set(true);
    this.importProgress.set(0);
    this.importTotal.set(0);

    this.persons.uploadCsvMasivo(file).subscribe({
      next: (res) => this.pollImportStatus(res.jobId),
      error: () => {
        this.importing.set(false);
        this.notifications.error('Error al iniciar la importación.');
      }
    });
  }

  private pollImportStatus(jobId: string): void {
    timer(0, 1000).pipe(
      switchMap(() => this.persons.getImportStatus(jobId).pipe(catchError(() => of({ status: 'error', progress: 0, total: 0 })))),
      takeWhile(res => res.status !== 'completed' && res.status !== 'error', true),
      takeUntilDestroyed(this.destroyRef)
    ).subscribe({
      next: (res) => {
        this.importProgress.set(res.progress);
        this.importTotal.set(res.total);
        if (res.status === 'completed') {
          this.importing.set(false);
          this.notifications.success('¡Importación masiva completada!');
          this.store.reload();
        } else if (res.status === 'error') {
          this.importing.set(false);
          this.notifications.error('Ocurrió un error en la importación.');
        }
      }
    });
  }

  // ---------------------------------------------------------------- Exportación
  protected exportExcel(): void {
    this.exportingExcel.set(true);
    this.persons.exportExcel(this.store.filters()).subscribe({
      next: (blob) => {
        this.downloadBlob(blob, 'Empleados.xlsx');
        this.exportingExcel.set(false);
        this.notifications.success('Excel exportado correctamente.');
      },
      error: () => {
        this.exportingExcel.set(false);
        this.notifications.error('Error al exportar Excel.');
      }
    });
  }

  protected exportPdf(): void {
    this.exportingPdf.set(true);
    this.persons.exportPdf(this.store.filters()).subscribe({
      next: (blob) => {
        this.downloadBlob(blob, 'Reporte_Empleados.pdf');
        this.exportingPdf.set(false);
        this.notifications.success('PDF exportado correctamente.');
      },
      error: () => {
        this.exportingPdf.set(false);
        this.notifications.error('Error al exportar PDF.');
      }
    });
  }

  private downloadBlob(blob: Blob, filename: string): void {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }

}
