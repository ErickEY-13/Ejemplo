import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { takeUntilDestroyed, toSignal } from '@angular/core/rxjs-interop';
import { Router, RouterLink } from '@angular/router';
import { Subject, of } from 'rxjs';
import { catchError, debounceTime, distinctUntilChanged, switchMap } from 'rxjs/operators';
import { ButtonModule } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';
import { TagModule } from 'primeng/tag';
import { TextareaModule } from 'primeng/textarea';

import { NotificationService } from '../../../../core/notifications/notification.service';
import { ConfirmService } from '../../../../shared/components/confirm-dialog/confirm.service';
import { IconComponent } from '../../../../shared/components/icon/icon';
import { SpinnerComponent } from '../../../../shared/components/spinner/spinner';
import { AssignmentService } from '../../../assignments/services/assignment.service';
import { PersonOption, Site, VehicleAssignment } from '../../../assignments/models/assignment.model';
import { Vehicle } from '../../models/vehicle.model';
import { VehicleService } from '../../services/vehicle.service';

/**
 * Detalle de solo lectura de un vehículo. Desde aquí se gestiona también la
 * foto (subir, reemplazar, quitar): al ser una acción independiente con su
 * propio endpoint, no tiene sentido meterla en el formulario de alta/edición.
 */
@Component({
  selector: 'app-vehicle-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    RouterLink,
    IconComponent,
    SpinnerComponent,
    DatePipe,
    ButtonModule,
    InputTextModule,
    TagModule,
    TextareaModule,
  ],
  templateUrl: './vehicle-detail.page.html',
  styleUrl: './vehicle-detail.page.scss',
})
export class VehicleDetailPage {
  readonly id = input.required<string>();

  private readonly vehicles = inject(VehicleService);
  private readonly assignments = inject(AssignmentService);
  private readonly confirm = inject(ConfirmService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);

  protected readonly vehicle = signal<Vehicle | null>(null);
  protected readonly loading = signal(true);
  protected readonly uploadingPhoto = signal(false);

  protected readonly assignment = signal<VehicleAssignment | null>(null);
  protected readonly assignmentLoading = signal(true);
  protected readonly assigning = signal(false);
  protected readonly showAssignForm = signal(false);
  protected readonly selectedSiteId = signal<number | null>(null);
  protected readonly personQuery = signal('');
  protected readonly personResults = signal<PersonOption[]>([]);
  protected readonly searchingPeople = signal(false);
  protected readonly selectedPerson = signal<PersonOption | null>(null);
  protected readonly assignmentNotes = signal('');
  protected readonly expectedReturnAt = signal('');
  protected readonly assignedAt = signal('');
  protected readonly todayIso = new Date().toISOString().slice(0, 10);

  protected readonly history = signal<VehicleAssignment[]>([]);
  protected readonly historyLoading = signal(true);

  protected readonly sites = toSignal(
    this.assignments.getSites().pipe(catchError(() => of([] as Site[]))),
    { initialValue: [] as Site[] },
  );

  /** Texto del buscador de personas, con retardo para no lanzar una petición por tecla. */
  private readonly personSearch = new Subject<string>();

  constructor() {
    effect(() => {
      const id = this.id();

      this.showAssignForm.set(false);
      this.selectedSiteId.set(null);
      this.personQuery.set('');
      this.personResults.set([]);
      this.selectedPerson.set(null);
      this.assignmentNotes.set('');
      this.expectedReturnAt.set('');
      this.assignedAt.set('');

      this.load(id);
      this.loadAssignment(id);
      this.loadHistory(id);
    });

    this.personSearch
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        switchMap((term) => {
          if (term.trim().length < 2) {
            this.searchingPeople.set(false);
            return of([]);
          }

          this.searchingPeople.set(true);

          return this.assignments.searchPeople(term).pipe(catchError(() => of([])));
        }),
        takeUntilDestroyed(),
      )
      .subscribe((results) => {
        this.personResults.set(results);
        this.searchingPeople.set(false);
      });
  }

  protected onPhotoSelected(input: HTMLInputElement): void {
    const file = input.files?.[0];

    if (!file) {
      return;
    }

    this.uploadingPhoto.set(true);

    this.vehicles.uploadPhoto(this.id(), file).subscribe({
      next: (vehicle) => {
        this.vehicle.set(vehicle);
        this.uploadingPhoto.set(false);
        this.notifications.success('La foto se actualizó correctamente.');
        input.value = '';
      },
      error: () => {
        this.uploadingPhoto.set(false);
        input.value = '';
      },
    });
  }

  protected async removePhoto(): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Quitar foto',
      message: '¿Seguro que quieres quitar la foto de este vehículo?',
      confirmLabel: 'Quitar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.vehicles.deletePhoto(this.id()).subscribe({
      next: (vehicle) => {
        this.vehicle.set(vehicle);
        this.notifications.success('La foto se quitó correctamente.');
      },
    });
  }

  protected async remove(vehicle: Vehicle): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Eliminar vehículo',
      message: `¿Seguro que quieres eliminar el vehículo de placa ${vehicle.plate}? Podrás restaurarlo más adelante.`,
      confirmLabel: 'Eliminar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.vehicles.remove(vehicle.id).subscribe({
      next: () => {
        this.notifications.success(`El vehículo ${vehicle.plate} se eliminó correctamente.`);
        void this.router.navigate(['/vehiculos']);
      },
    });
  }

  protected restore(vehicle: Vehicle): void {
    this.vehicles.restore(vehicle.id).subscribe({
      next: (updated) => {
        this.vehicle.set(updated);
        this.notifications.success(`El vehículo ${vehicle.plate} se restauró correctamente.`);
      },
    });
  }

  protected onSiteChange(value: string): void {
    this.selectedSiteId.set(value ? Number(value) : null);
  }

  protected onPersonSearch(value: string): void {
    this.personQuery.set(value);
    this.selectedPerson.set(null);
    this.personSearch.next(value);
  }

  protected selectPerson(person: PersonOption): void {
    this.selectedPerson.set(person);
    this.personQuery.set(person.full_name);
    this.personResults.set([]);
  }

  protected assign(): void {
    const siteId = this.selectedSiteId();

    if (!siteId) {
      return;
    }

    this.assigning.set(true);

    this.assignments
      .assign(this.id(), {
        site_id: siteId,
        person_id: this.selectedPerson()?.id ?? null,
        assigned_at: this.assignedAt() || null,
        expected_return_at: this.expectedReturnAt() || null,
        notes: this.assignmentNotes().trim() || null,
      })
      .subscribe({
        next: (assignment) => {
          this.assignment.set(assignment);
          this.assigning.set(false);
          this.showAssignForm.set(false);
          this.selectedSiteId.set(null);
          this.personQuery.set('');
          this.selectedPerson.set(null);
          this.assignmentNotes.set('');
          this.expectedReturnAt.set('');
          this.assignedAt.set('');
          this.loadHistory(this.id());
          this.notifications.success(
            assignment.person
              ? `Vehículo trasladado a ${assignment.site?.name} y asignado a ${assignment.person.full_name}.`
              : `Vehículo trasladado a ${assignment.site?.name}.`,
          );
        },
        error: () => this.assigning.set(false),
      });
  }

  protected async unassign(): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Quitar asignación',
      message: '¿Seguro que quieres quitar la asignación de este vehículo?',
      confirmLabel: 'Quitar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.assignments.unassign(this.id()).subscribe({
      next: () => {
        this.assignment.set(null);
        this.loadHistory(this.id());
        this.notifications.success('Se quitó la asignación del vehículo.');
      },
    });
  }

  /** Texto breve de cuánto tiempo lleva asignado, a partir de la fecha ISO. */
  protected assignmentDuration(assignedAt: string): string {
    const days = Math.max(0, Math.floor((Date.now() - new Date(assignedAt).getTime()) / 86_400_000));

    if (days === 0) {
      return 'hoy';
    }

    if (days === 1) {
      return 'hace 1 día';
    }

    if (days < 30) {
      return `hace ${days} días`;
    }

    const months = Math.round(days / 30);

    return months === 1 ? 'hace 1 mes' : `hace ${months} meses`;
  }

  private load(id: string): void {
    this.loading.set(true);

    this.vehicles.find(id).subscribe({
      next: (vehicle) => {
        this.vehicle.set(vehicle);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        void this.router.navigate(['/vehiculos']);
      },
    });
  }

  private loadAssignment(id: string): void {
    this.assignmentLoading.set(true);

    this.assignments.current(id).subscribe({
      next: (assignment) => {
        this.assignment.set(assignment);
        this.assignmentLoading.set(false);
      },
      error: () => this.assignmentLoading.set(false),
    });
  }

  private loadHistory(id: string): void {
    this.historyLoading.set(true);

    this.assignments.history(id).subscribe({
      next: (history) => {
        this.history.set(history);
        this.historyLoading.set(false);
      },
      error: () => this.historyLoading.set(false),
    });
  }
}
