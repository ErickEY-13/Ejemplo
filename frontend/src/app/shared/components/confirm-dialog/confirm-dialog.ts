import { ChangeDetectionStrategy, Component, inject } from '@angular/core';

import { IconComponent } from '../icon/icon';
import { ConfirmService } from './confirm.service';

/**
 * Punto de montaje del diálogo de confirmación. Se declara una sola vez en el
 * shell; los módulos lo invocan a través de `ConfirmService`.
 */
@Component({
  selector: 'app-confirm-dialog',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [IconComponent],
  host: {
    '(document:keydown.escape)': 'onEscape()',
  },
  template: `
    @let request = confirm.current();

    @if (request) {
      <div class="backdrop" (click)="confirm.respond(false)">
        <div
          class="dialog"
          role="alertdialog"
          aria-modal="true"
          aria-labelledby="confirm-title"
          (click)="$event.stopPropagation()"
        >
          <span class="dialog__icon" [class.dialog__icon--danger]="request.danger">
            <app-icon [name]="request.danger ? 'alert-triangle' : 'info-circle'" [size]="22" />
          </span>

          <h2 class="dialog__title" id="confirm-title">{{ request.title }}</h2>
          <p class="dialog__message">{{ request.message }}</p>

          <div class="dialog__actions">
            <button type="button" class="btn btn--ghost" (click)="confirm.respond(false)">
              {{ request.cancelLabel ?? 'Cancelar' }}
            </button>
            <button
              type="button"
              class="btn"
              [class.btn--danger]="request.danger"
              [class.btn--primary]="!request.danger"
              autofocus
              (click)="confirm.respond(true)"
            >
              {{ request.confirmLabel ?? 'Confirmar' }}
            </button>
          </div>
        </div>
      </div>
    }
  `,
  styles: `
    .backdrop {
      position: fixed;
      inset: 0;
      z-index: 60;
      display: grid;
      place-items: center;
      padding: var(--sp-4);
      background: rgba(15, 23, 42, 0.45);
    }

    .dialog {
      width: min(430px, 100%);
      padding: var(--sp-5);
      background: var(--c-surface);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
    }

    .dialog__icon {
      display: grid;
      place-items: center;
      width: 44px;
      height: 44px;
      margin-bottom: var(--sp-4);
      border-radius: 50%;
      background: var(--c-primary-soft);
      color: var(--c-primary-dark);
    }

    .dialog__icon--danger {
      background: var(--c-danger-soft);
      color: var(--c-danger);
    }

    .dialog__title {
      font-size: 1.1rem;
    }

    .dialog__message {
      margin: var(--sp-3) 0 var(--sp-5);
      color: var(--c-text-muted);
    }

    .dialog__actions {
      display: flex;
      justify-content: flex-end;
      gap: var(--sp-2);
    }
  `,
})
export class ConfirmDialogComponent {
  protected readonly confirm = inject(ConfirmService);

  protected onEscape(): void {
    if (this.confirm.current()) {
      this.confirm.respond(false);
    }
  }
}
