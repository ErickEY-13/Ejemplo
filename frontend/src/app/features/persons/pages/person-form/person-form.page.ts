import { ChangeDetectionStrategy, Component, computed, effect, inject, input, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { of } from 'rxjs';
import { catchError } from 'rxjs/operators';

import { ApiError } from '../../../../core/api/api.types';
import { NotificationService } from '../../../../core/notifications/notification.service';
import { IconComponent } from '../../../../shared/components/icon/icon';
import { SpinnerComponent } from '../../../../shared/components/spinner/spinner';
import { applyServerErrors, firstErrorMessage } from '../../../../shared/forms/server-errors';
import { PersonMetadata, PersonPayload } from '../../models/person.model';
import { PersonService } from '../../services/person.service';

/**
 * Alta y edición de personas. La misma pantalla sirve para ambos casos:
 * con `id` en la ruta edita, sin él crea.
 */
@Component({
  selector: 'app-person-form',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, RouterLink, IconComponent, SpinnerComponent],
  templateUrl: './person-form.page.html',
  styleUrl: './person-form.page.scss',
})
export class PersonFormPage {
  /** Llega desde la ruta `:id/editar` gracias a `withComponentInputBinding()`. */
  readonly id = input<string>();

  private readonly persons = inject(PersonService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => !!this.id());
  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly generalErrors = signal<string[]>([]);

  protected readonly metadata = toSignal(
    this.persons.metadata().pipe(catchError(() => of(null as PersonMetadata | null))),
    { initialValue: null },
  );

  protected readonly form = this.fb.nonNullable.group({
    document_type: ['national_id', [Validators.required]],
    document_number: ['', [Validators.required, Validators.maxLength(32)]],
    first_name: ['', [Validators.required, Validators.maxLength(100)]],
    last_name: ['', [Validators.required, Validators.maxLength(100)]],
    birth_date: [''],
    gender: ['undisclosed'],
    email: ['', [Validators.email, Validators.maxLength(150)]],
    phone: ['', [Validators.maxLength(30)]],
    address: ['', [Validators.maxLength(255)]],
    site: ['', [Validators.maxLength(100)]],
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

      this.persons.find(id).subscribe({
        next: (person) => {
          this.form.patchValue({
            document_type: person.document_type,
            document_number: person.document_number,
            first_name: person.first_name,
            last_name: person.last_name,
            birth_date: person.birth_date ?? '',
            gender: person.gender,
            email: person.email ?? '',
            phone: person.phone ?? '',
            address: person.address ?? '',
            site: person.site ?? '',
            is_active: person.is_active,
            notes: person.notes ?? '',
          });
          this.loading.set(false);
        },
        error: () => {
          this.loading.set(false);
          void this.router.navigate(['/personas']);
        },
      });
    });
  }

  protected errorFor(field: string, label: string): string | null {
    return firstErrorMessage(this.form, field, label);
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
    const request = id ? this.persons.update(id, payload) : this.persons.create(payload);

    this.saving.set(true);

    request.subscribe({
      next: (person) => {
        this.saving.set(false);
        this.notifications.success(
          id ? `${person.full_name} se actualizó correctamente.` : `${person.full_name} se registró correctamente.`,
        );
        void this.router.navigate(['/personas']);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.generalErrors.set(applyServerErrors(this.form, error));
      },
    });
  }

  /** Convierte las cadenas vacías del formulario en `null` para la API. */
  private toPayload(): PersonPayload {
    const value = this.form.getRawValue();
    const blankToNull = (input: string): string | null => (input.trim() === '' ? null : input.trim());

    return {
      document_type: value.document_type,
      document_number: value.document_number.trim(),
      first_name: value.first_name.trim(),
      last_name: value.last_name.trim(),
      birth_date: blankToNull(value.birth_date),
      gender: value.gender,
      email: blankToNull(value.email),
      phone: blankToNull(value.phone),
      address: blankToNull(value.address),
      site: blankToNull(value.site),
      is_active: value.is_active,
      notes: blankToNull(value.notes),
    };
  }
}
