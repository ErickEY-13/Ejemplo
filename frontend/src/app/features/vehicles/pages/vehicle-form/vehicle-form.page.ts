import { ChangeDetectionStrategy, Component, computed, effect, inject, input, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { of } from 'rxjs';
import { catchError, switchMap } from 'rxjs/operators';
import { ButtonModule } from 'primeng/button';
import { CardModule } from 'primeng/card';
import { CheckboxModule } from 'primeng/checkbox';
import { InputNumberModule } from 'primeng/inputnumber';
import { InputTextModule } from 'primeng/inputtext';
import { MessageModule } from 'primeng/message';
import { SelectModule } from 'primeng/select';
import { TextareaModule } from 'primeng/textarea';

import { ApiError } from '../../../../core/api/api.types';
import { NotificationService } from '../../../../core/notifications/notification.service';
import { IconComponent } from '../../../../shared/components/icon/icon';
import { SpinnerComponent } from '../../../../shared/components/spinner/spinner';
import { applyServerErrors, firstErrorMessage } from '../../../../shared/forms/server-errors';
import { VehicleMetadata, VehiclePayload } from '../../models/vehicle.model';
import { VehicleService } from '../../services/vehicle.service';

const CURRENT_YEAR = new Date().getFullYear();

/**
 * Alta y edición de vehículos. La misma pantalla sirve para ambos casos:
 * con `id` en la ruta edita, sin él crea.
 */
@Component({
  selector: 'app-vehicle-form',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    ReactiveFormsModule,
    RouterLink,
    IconComponent,
    SpinnerComponent,
    ButtonModule,
    CardModule,
    CheckboxModule,
    InputNumberModule,
    InputTextModule,
    MessageModule,
    SelectModule,
    TextareaModule,
  ],
  templateUrl: './vehicle-form.page.html',
  styleUrl: './vehicle-form.page.scss',
})
export class VehicleFormPage {
  /** Llega desde la ruta `:id/editar` gracias a `withComponentInputBinding()`. */
  readonly id = input<string>();

  private readonly vehicles = inject(VehicleService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => !!this.id());
  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly generalErrors = signal<string[]>([]);
  protected readonly maxYear = CURRENT_YEAR + 1;

  /** URL remota o data URL local que se muestra en la vista previa. */
  protected readonly photoPreview = signal<string | null>(null);
  /** Solo se usa al crear: en edición la foto se sube en cuanto se elige. */
  private photoFile: File | null = null;

  protected readonly metadata = toSignal(
    this.vehicles.metadata().pipe(catchError(() => of(null as VehicleMetadata | null))),
    { initialValue: null },
  );

  protected readonly form = this.fb.nonNullable.group({
    plate: ['', [Validators.required, Validators.maxLength(15), Validators.pattern(/^[A-Za-z0-9-]{5,15}$/)]],
    brand: ['', [Validators.required, Validators.maxLength(60)]],
    model: ['', [Validators.required, Validators.maxLength(60)]],
    year: [CURRENT_YEAR, [Validators.required, Validators.min(1900), Validators.max(CURRENT_YEAR + 1)]],
    type: ['car', [Validators.required]],
    fuel_type: ['gasoline'],
    color: ['', [Validators.maxLength(40)]],
    vin: ['', [Validators.pattern(/^[A-HJ-NPR-Za-hj-npr-z0-9]{17}$/)]],
    engine_number: ['', [Validators.maxLength(40)]],
    mileage: [0, [Validators.min(0), Validators.max(9_999_999)]],
    is_active: [true],
    notes: ['', [Validators.maxLength(2000)]],
  });

  constructor() {
    // Carga el registro cuando la ruta trae un id.
    effect(() => {
      const id = this.id();

      if (!id) {
        return;
      }

      this.loading.set(true);

      this.vehicles.find(id).subscribe({
        next: (vehicle) => {
          this.form.patchValue({
            plate: vehicle.plate,
            brand: vehicle.brand,
            model: vehicle.model,
            year: vehicle.year,
            type: vehicle.type,
            fuel_type: vehicle.fuel_type,
            color: vehicle.color ?? '',
            vin: vehicle.vin ?? '',
            engine_number: vehicle.engine_number ?? '',
            mileage: vehicle.mileage,
            is_active: vehicle.is_active,
            notes: vehicle.notes ?? '',
          });
          this.photoPreview.set(vehicle.photo_url ?? null);
          this.loading.set(false);
        },
        error: () => {
          this.loading.set(false);
          void this.router.navigate(['/vehiculos']);
        },
      });
    });
  }

  protected errorFor(field: string, label: string): string | null {
    return firstErrorMessage(this.form, field, label);
  }

  // ---------------------------------------------------------------- Foto

  protected onPhotoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
      return;
    }

    if (file.size > 4 * 1024 * 1024) {
      this.notifications.error('La foto no puede pesar más de 4 MB.');
      return;
    }

    this.photoFile = file;

    // Vista previa inmediata, sin esperar al backend.
    const reader = new FileReader();
    reader.onload = () => this.photoPreview.set(reader.result as string);
    reader.readAsDataURL(file);

    // Editando ya existe el vehículo: se sube al momento para no perder la
    // foto si el usuario se marcha sin guardar. Al crear no hay id todavía,
    // así que la subida se encadena en submit().
    const id = this.id();
    if (id) {
      this.vehicles.uploadPhoto(id, file).subscribe({
        next: (vehicle) => {
          this.photoPreview.set(vehicle.photo_url);
          this.photoFile = null;
          this.notifications.success('Foto actualizada correctamente.');
        },
        error: () => this.notifications.error('No se pudo subir la foto.'),
      });
    }

    // Permite volver a elegir el mismo archivo tras un error.
    input.value = '';
  }

  protected removePhoto(): void {
    const id = this.id();

    if (!id) {
      this.photoPreview.set(null);
      this.photoFile = null;
      return;
    }

    this.vehicles.deletePhoto(id).subscribe({
      next: () => {
        this.photoPreview.set(null);
        this.photoFile = null;
        this.notifications.success('Foto eliminada.');
      },
      error: () => this.notifications.error('No se pudo eliminar la foto.'),
    });
  }

  protected submit(): void {
    this.generalErrors.set([]);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.notifications.error('Revisa los campos marcados en rojo.');
      return;
    }

    const payload = this.toPayload();
    const id = this.id();
    const photo = this.photoFile;

    // Al crear, la foto solo puede subirse cuando el vehículo ya tiene id.
    const request = id
      ? this.vehicles.update(id, payload)
      : this.vehicles
          .create(payload)
          .pipe(
            switchMap((vehicle) =>
              photo
                ? this.vehicles.uploadPhoto(vehicle.id, photo).pipe(
                    // El vehículo ya está creado: un fallo de la foto no debe
                    // deshacer el alta, solo avisar de que hay que reintentarla.
                    catchError(() => {
                      this.notifications.error(
                        'El vehículo se registró, pero no se pudo subir la foto. Vuelve a intentarlo desde la edición.',
                      );
                      return of(vehicle);
                    }),
                  )
                : of(vehicle),
            ),
          );

    this.saving.set(true);

    request.subscribe({
      next: (vehicle) => {
        this.saving.set(false);
        this.notifications.success(
          id
            ? `El vehículo ${vehicle.plate} se actualizó correctamente.`
            : `El vehículo ${vehicle.plate} se registró correctamente.`,
        );
        void this.router.navigate(['/vehiculos']);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.generalErrors.set(applyServerErrors(this.form, error));
      },
    });
  }

  /** Normaliza el formulario al formato que espera la API. */
  private toPayload(): VehiclePayload {
    const value = this.form.getRawValue();
    const blankToNull = (input: string): string | null => (input.trim() === '' ? null : input.trim());

    return {
      plate: value.plate.trim().toUpperCase(),
      brand: value.brand.trim(),
      model: value.model.trim(),
      year: Number(value.year),
      type: value.type,
      fuel_type: value.fuel_type,
      color: blankToNull(value.color),
      vin: value.vin.trim() === '' ? null : value.vin.trim().toUpperCase(),
      engine_number: blankToNull(value.engine_number),
      mileage: Number(value.mileage) || 0,
      is_active: value.is_active,
      notes: blankToNull(value.notes),
    };
  }
}
