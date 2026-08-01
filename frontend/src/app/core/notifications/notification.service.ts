import { Injectable, signal } from '@angular/core';

export type NotificationKind = 'success' | 'error' | 'info';

export interface Notification {
  id: number;
  kind: NotificationKind;
  message: string;
}

/**
 * Avisos efímeros (toasts) compartidos por toda la aplicación.
 *
 * Los emite tanto el interceptor de errores como los módulos tras una
 * operación correcta.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly items = signal<Notification[]>([]);
  private nextId = 1;

  /** Solo lectura para la vista. */
  readonly notifications = this.items.asReadonly();

  success(message: string): void {
    this.push('success', message);
  }

  error(message: string): void {
    this.push('error', message, 8000);
  }

  info(message: string): void {
    this.push('info', message);
  }

  dismiss(id: number): void {
    this.items.update((list) => list.filter((item) => item.id !== id));
  }

  private push(kind: NotificationKind, message: string, timeout = 5000): void {
    const id = this.nextId++;

    this.items.update((list) => [...list, { id, kind, message }]);
    setTimeout(() => this.dismiss(id), timeout);
  }
}
