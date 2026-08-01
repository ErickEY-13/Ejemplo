import { Injectable, signal } from '@angular/core';

export interface ConfirmOptions {
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  danger?: boolean;
}

interface PendingConfirm extends ConfirmOptions {
  resolve: (accepted: boolean) => void;
}

/**
 * Diálogo de confirmación con API de promesa:
 *
 * ```ts
 * if (await this.confirm.ask({ title: '…', message: '…' })) { … }
 * ```
 *
 * El componente que lo pinta (`ConfirmDialogComponent`) vive una sola vez en
 * el shell de la aplicación.
 */
@Injectable({ providedIn: 'root' })
export class ConfirmService {
  private readonly pending = signal<PendingConfirm | null>(null);

  readonly current = this.pending.asReadonly();

  ask(options: ConfirmOptions): Promise<boolean> {
    return new Promise<boolean>((resolve) => {
      this.pending.set({ ...options, resolve });
    });
  }

  respond(accepted: boolean): void {
    const request = this.pending();
    this.pending.set(null);
    request?.resolve(accepted);
  }
}
