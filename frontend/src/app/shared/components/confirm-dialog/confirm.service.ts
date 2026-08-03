import { Injectable, inject } from '@angular/core';
import { ConfirmationService } from 'primeng/api';

export interface ConfirmOptions {
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  danger?: boolean;
}

/**
 * Diálogo de confirmación con API de promesa:
 *
 * ```ts
 * if (await this.confirm.ask({ title: '…', message: '…' })) { … }
 * ```
 *
 * Se pinta con `p-confirmdialog`, montado una sola vez en el shell de la
 * aplicación (`app.html`).
 */
@Injectable({ providedIn: 'root' })
export class ConfirmService {
  private readonly confirmation = inject(ConfirmationService);

  ask(options: ConfirmOptions): Promise<boolean> {
    return new Promise<boolean>((resolve) => {
      this.confirmation.confirm({
        header: options.title,
        message: options.message,
        acceptLabel: options.confirmLabel ?? 'Confirmar',
        rejectLabel: options.cancelLabel ?? 'Cancelar',
        icon: options.danger ? 'pi pi-exclamation-triangle' : 'pi pi-info-circle',
        acceptButtonProps: {
          severity: options.danger ? 'danger' : 'primary',
        },
        rejectButtonProps: {
          severity: 'secondary',
          outlined: true,
        },
        accept: () => resolve(true),
        reject: () => resolve(false),
      });
    });
  }
}
