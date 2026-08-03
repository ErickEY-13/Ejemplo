import { ChangeDetectionStrategy, Component, computed, effect, inject, input, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { forkJoin, of, Observable } from 'rxjs';
import { catchError, switchMap } from 'rxjs/operators';
import { ButtonModule } from 'primeng/button';
import { CardModule } from 'primeng/card';
import { CheckboxModule } from 'primeng/checkbox';
import { InputNumberModule } from 'primeng/inputnumber';
import { InputTextModule } from 'primeng/inputtext';
import { MessageModule } from 'primeng/message';
import { SelectModule } from 'primeng/select';
import { TagModule } from 'primeng/tag';
import { TextareaModule } from 'primeng/textarea';
import { TooltipModule } from 'primeng/tooltip';

import { ApiError } from '../../../../core/api/api.types';
import { NotificationService } from '../../../../core/notifications/notification.service';
import { ConfirmService } from '../../../../shared/components/confirm-dialog/confirm.service';
import { IconComponent } from '../../../../shared/components/icon/icon';
import { SpinnerComponent } from '../../../../shared/components/spinner/spinner';
import { applyServerErrors, firstErrorMessage } from '../../../../shared/forms/server-errors';
import { Audit, PersonDocument, PersonMetadata, PersonPayload } from '../../models/person.model';
import { PersonService } from '../../services/person.service';

import { DatePipe } from '@angular/common';

/**
 * Alta y edición de personas. La misma pantalla sirve para ambos casos:
 * con `id` en la ruta edita, sin él crea.
 */
@Component({
  selector: 'app-person-form',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    ReactiveFormsModule,
    FormsModule,
    RouterLink,
    IconComponent,
    SpinnerComponent,
    DatePipe,
    ButtonModule,
    CardModule,
    CheckboxModule,
    InputNumberModule,
    InputTextModule,
    MessageModule,
    SelectModule,
    TagModule,
    TextareaModule,
    TooltipModule,
  ],
  templateUrl: './person-form.page.html',
  styleUrl: './person-form.page.scss',
})
export class PersonFormPage {
  /** Llega desde la ruta `:id/editar` gracias a `withComponentInputBinding()`. */
  readonly id = input<string>();

  private readonly persons = inject(PersonService);
  private readonly notifications = inject(NotificationService);
  private readonly confirm = inject(ConfirmService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => !!this.id());
  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly generalErrors = signal<string[]>([]);

  /** Historial de auditoría. */
  protected readonly audits = signal<Audit[]>([]);
  
  /** PDF */
  protected readonly downloadingPdf = signal(false);

  /** Preview de la foto (data URL local o URL del servidor). */
  protected readonly photoPreview = signal<string | null>(null);
  private photoFile: File | null = null;
  private photoRemoved = false;

  /** Documentos adjuntos de la persona. */
  protected readonly documents = signal<PersonDocument[]>([]);
  protected readonly pendingDocuments = signal<{ file: File; type: string; typeLabel: string }[]>([]);
  protected readonly uploadingDoc = signal(false);
  protected docType = 'cv';

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
    marital_status: [''],
    education_level: [''],
    children_count: [null as number | null],
    email: ['', [Validators.email, Validators.maxLength(150)]],
    phone: ['', [Validators.maxLength(30)]],
    emergency_contact_name: ['', [Validators.maxLength(200)]],
    emergency_contact_phone: ['', [Validators.maxLength(30)]],
    address: ['', [Validators.maxLength(255)]],
    ruc: ['', [Validators.maxLength(11)]],
    pension_system: [''],
    site: [''],
    area: [''],
    position: ['', [Validators.maxLength(100)]],
    contract_type: [''],
    hire_date: [''],
    work_shift: [''],
    is_active: [true],
    notes: ['', [Validators.maxLength(2000)]],
  });

  /** Iniciales del nombre para el avatar por defecto. */
  protected readonly initials = computed(() => {
    const first = this.form.controls.first_name.value;
    const last = this.form.controls.last_name.value;
    const f = first?.charAt(0)?.toUpperCase() ?? '';
    const l = last?.charAt(0)?.toUpperCase() ?? '';
    return f + l || '?';
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
            marital_status: person.marital_status ?? '',
            education_level: person.education_level ?? '',
            children_count: person.children_count,
            email: person.email ?? '',
            phone: person.phone ?? '',
            emergency_contact_name: person.emergency_contact_name ?? '',
            emergency_contact_phone: person.emergency_contact_phone ?? '',
            address: person.address ?? '',
            ruc: person.ruc ?? '',
            pension_system: person.pension_system ?? '',
            site: person.site ?? '',
            area: person.area ?? '',
            position: person.position ?? '',
            contract_type: person.contract_type ?? '',
            hire_date: person.hire_date ?? '',
            work_shift: person.work_shift ?? '',
            is_active: person.is_active,
            notes: person.notes ?? '',
          });

          if (person.photo_url) {
            this.photoPreview.set(person.photo_url);
          }

          this.documents.set(person.documents ?? []);
          this.loading.set(false);

          // Cargar historial de auditoría
          this.persons.getAudits(id).subscribe({
            next: (audits) => this.audits.set(audits),
          });
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
  
  protected objectKeys(obj: any): string[] {
    return obj ? Object.keys(obj) : [];
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
    this.photoRemoved = false;

    // Preview local
    const reader = new FileReader();
    reader.onload = () => this.photoPreview.set(reader.result as string);
    reader.readAsDataURL(file);

    // Si estamos editando, subir de inmediato
    const id = this.id();
    if (id) {
      this.persons.uploadPhoto(id, file).subscribe({
        next: (person) => {
          this.photoPreview.set(person.photo_url);
          this.photoFile = null;
          this.notifications.success('Foto actualizada correctamente.');
        },
        error: () => this.notifications.error('No se pudo subir la foto.'),
      });
    }

    // Reset del input para poder seleccionar el mismo archivo de nuevo
    input.value = '';
  }

  protected removePhoto(): void {
    const id = this.id();
    if (!id) {
      return;
    }

    this.persons.deletePhoto(id).subscribe({
      next: () => {
        this.photoPreview.set(null);
        this.photoFile = null;
        this.photoRemoved = true;
        this.notifications.success('Foto eliminada.');
      },
      error: () => this.notifications.error('No se pudo eliminar la foto.'),
    });
  }

  // ---------------------------------------------------------- Documentos

  protected onDocumentSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    const id = this.id();
    
    // Si ya existe la persona, lo subimos de inmediato
    if (id) {
      this.uploadingDoc.set(true);
      this.persons.uploadDocument(id, file, this.docType).subscribe({
        next: (doc) => {
          this.documents.update((docs) => [...docs, doc]);
          this.uploadingDoc.set(false);
          this.notifications.success('Documento subido correctamente.');
        },
        error: () => {
          this.uploadingDoc.set(false);
          this.notifications.error('Error al subir el documento.');
        },
      });
    } else {
      // Si estamos creando la persona, lo guardamos en memoria para subirlo al final
      const meta = this.metadata()?.document_types_attach;
      const typeLabel = meta?.find(t => t.value === this.docType)?.label || this.docType;
      
      this.pendingDocuments.update(docs => [...docs, { file, type: this.docType, typeLabel }]);
      this.notifications.success('Documento adjuntado. Se subirá al guardar la persona.');
    }
    
    // Limpiar el input
    input.value = '';
  }

  protected async removeDocument(doc: PersonDocument): Promise<void> {
    const id = this.id();
    if (!id) return;

    const accepted = await this.confirm.ask({
      title: 'Eliminar documento',
      message: `¿Seguro que quieres eliminar el documento "${doc.original_name}"?`,
      confirmLabel: 'Eliminar',
      danger: true,
    });

    if (!accepted) return;

    this.persons.deleteDocument(id, doc.id).subscribe({
      next: () => {
        this.documents.update((docs) => docs.filter((d) => d.id !== doc.id));
        this.notifications.success('Documento eliminado.');
      },
      error: () => this.notifications.error('Error al eliminar el documento.'),
    });
  }

  protected removePendingDocument(index: number): void {
    this.pendingDocuments.update(docs => docs.filter((_, i) => i !== index));
  }

  protected formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  }

  // ------------------------------------------------------------- Entrevista

  protected downloadPdf(): void {
    const id = this.id();
    if (!id) return;

    this.downloadingPdf.set(true);

    this.persons.downloadPdf(id).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        
        // El nombre viene del backend, pero podemos forzar uno por si acaso
        const docNumber = this.form.controls.document_number.value || 'desconocido';
        link.download = `Ficha_Personal_${docNumber}.pdf`;
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        this.downloadingPdf.set(false);
        this.notifications.success('PDF descargado correctamente.');
      },
      error: () => {
        this.downloadingPdf.set(false);
        this.notifications.error('Hubo un error al generar el PDF.');
      }
    });
  }

  // ------------------------------------------------------------ Guardar

  protected submit(): void {
    this.generalErrors.set([]);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.notifications.error('Revisa los campos marcados en rojo.');
      return;
    }

    const payload = this.toPayload();
    const id = this.id();

    let request$: Observable<any>;

    if (this.isEdit()) {
      // Actualizar persona
      request$ = this.persons.update(id!, payload).pipe(
        switchMap(() => {
          const uploads: Observable<any>[] = [];
          
          if (this.photoFile) {
            uploads.push(this.persons.uploadPhoto(id!, this.photoFile));
          }
          
          return uploads.length ? forkJoin(uploads) : of(null);
        })
      );
    } else {
      // Crear persona
      request$ = this.persons.create(payload).pipe(
        switchMap((person) => {
          const uploads: Observable<any>[] = [];
          
          if (this.photoFile) {
            uploads.push(this.persons.uploadPhoto(person.id, this.photoFile));
          }
          
          // Subir todos los documentos pendientes
          this.pendingDocuments().forEach(doc => {
            uploads.push(this.persons.uploadDocument(person.id, doc.file, doc.type));
          });
          
          return uploads.length ? forkJoin(uploads) : of({ ...person, isNew: true });
        })
      );
    }

    this.saving.set(true);

    request$.subscribe({
      next: () => {
        this.saving.set(false);
        this.notifications.success(
          id ? `Persona actualizada correctamente.` : `Persona registrada y archivos adjuntados correctamente.`
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
      marital_status: blankToNull(value.marital_status),
      education_level: blankToNull(value.education_level),
      children_count: value.children_count,
      email: blankToNull(value.email),
      phone: blankToNull(value.phone),
      emergency_contact_name: blankToNull(value.emergency_contact_name),
      emergency_contact_phone: blankToNull(value.emergency_contact_phone),
      address: blankToNull(value.address),
      ruc: blankToNull(value.ruc),
      pension_system: blankToNull(value.pension_system),
      site: blankToNull(value.site),
      area: blankToNull(value.area),
      position: blankToNull(value.position),
      contract_type: blankToNull(value.contract_type),
      hire_date: blankToNull(value.hire_date),
      work_shift: blankToNull(value.work_shift),
      is_active: value.is_active,
      notes: blankToNull(value.notes),
    };
  }
}
