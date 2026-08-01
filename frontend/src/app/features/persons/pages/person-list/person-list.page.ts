import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed, toObservable, toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject, of } from 'rxjs';
import { catchError, debounceTime, distinctUntilChanged, switchMap, tap } from 'rxjs/operators';

import { EMPTY_META, Paginated } from '../../../../core/api/api.types';
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

const EMPTY_PAGE: Paginated<Person> = { data: [], meta: EMPTY_META };

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
  ],
  templateUrl: './person-list.page.html',
  styleUrl: './person-list.page.scss',
})
export class PersonListPage {
  private readonly persons = inject(PersonService);
  private readonly confirm = inject(ConfirmService);
  private readonly notifications = inject(NotificationService);

  /** Filtros activos; cualquier cambio dispara una nueva consulta. */
  protected readonly filters = signal<PersonFilters>({ ...DEFAULT_PERSON_FILTERS });
  protected readonly loading = signal(false);

  /** Se incrementa para forzar una recarga con los mismos filtros. */
  private readonly refreshToken = signal(0);

  private readonly query = computed(() => ({
    filters: this.filters(),
    token: this.refreshToken(),
  }));

  private readonly page = toSignal(
    toObservable(this.query).pipe(
      tap(() => this.loading.set(true)),
      switchMap(({ filters }) => this.persons.list(filters).pipe(catchError(() => of(EMPTY_PAGE)))),
      tap(() => this.loading.set(false)),
    ),
    { initialValue: EMPTY_PAGE },
  );

  protected readonly items = computed(() => this.page().data);
  protected readonly meta = computed(() => this.page().meta);
  protected readonly hasFilters = computed(() => {
    const { search, document_type, is_active, with_trashed } = this.filters();
    return search !== '' || document_type !== '' || is_active !== '' || with_trashed;
  });

  protected readonly metadata = toSignal(
    this.persons.metadata().pipe(catchError(() => of(null as PersonMetadata | null))),
    { initialValue: null },
  );

  /** Texto del buscador, con retardo para no lanzar una petición por tecla. */
  private readonly searchTerm = new Subject<string>();

  constructor() {
    this.searchTerm
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed())
      .subscribe((search) => this.patchFilters({ search }));
  }

  protected onSearch(value: string): void {
    this.searchTerm.next(value);
  }

  /** Aplica cambios de filtro y vuelve siempre a la primera página. */
  protected patchFilters(patch: Partial<PersonFilters>): void {
    this.filters.update((current) => ({ ...current, ...patch, page: patch.page ?? 1 }));
  }

  protected goToPage(page: number): void {
    this.filters.update((current) => ({ ...current, page }));
  }

  protected sortBy(column: string): void {
    this.filters.update((current) => ({
      ...current,
      sort: column,
      direction: current.sort === column && current.direction === 'asc' ? 'desc' : 'asc',
      page: 1,
    }));
  }

  /** Flecha de orden de la columna, o `null` si no es la que ordena. */
  protected sortIcon(column: string): IconName | null {
    const { sort, direction } = this.filters();

    if (sort !== column) {
      return null;
    }

    return direction === 'asc' ? 'chevron-up' : 'chevron-down';
  }

  protected resetFilters(): void {
    this.filters.set({ ...DEFAULT_PERSON_FILTERS });
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
        this.reload();
      },
    });
  }

  protected restore(person: Person): void {
    this.persons.restore(person.id).subscribe({
      next: () => {
        this.notifications.success(`${person.full_name} se restauró correctamente.`);
        this.reload();
      },
    });
  }

  private reload(): void {
    this.refreshToken.update((value) => value + 1);
  }
}
