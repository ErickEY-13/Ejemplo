import { Injectable, inject } from '@angular/core';
import { MessageService } from 'primeng/api';

export type NotificationKind = 'success' | 'error' | 'info';

/**
 * Avisos efímeros (toasts) compartidos por toda la aplicación.
 *
 * Los emite tanto el interceptor de errores como los módulos tras una
 * operación correcta. Se pintan con `p-toast`, montado una sola vez en el
 * shell (`app.html`).
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly messages = inject(MessageService);

  success(message: string): void {
    this.push('success', message);
  }

  error(message: string): void {
    this.push('error', message, 8000);
  }

  info(message: string): void {
    this.push('info', message);
  }

  private push(kind: NotificationKind, message: string, life = 5000): void {
    this.messages.add({
      severity: kind === 'error' ? 'error' : kind === 'success' ? 'success' : 'info',
      summary: this.summaryFor(kind),
      detail: message,
      life,
    });
  }

  private summaryFor(kind: NotificationKind): string {
    switch (kind) {
      case 'success':
        return 'Listo';
      case 'error':
        return 'Error';
      default:
        return 'Aviso';
    }
  }
}
