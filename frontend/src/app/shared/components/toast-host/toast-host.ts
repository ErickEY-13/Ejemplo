import { ChangeDetectionStrategy, Component, inject } from '@angular/core';

import {
  NotificationKind,
  NotificationService,
} from '../../../core/notifications/notification.service';
import { IconComponent, IconName } from '../icon/icon';

/** Pinta los avisos de `NotificationService`. Se declara una vez en el shell. */
@Component({
  selector: 'app-toast-host',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [IconComponent],
  template: `
    <div class="toasts" aria-live="polite" aria-atomic="false">
      @for (toast of notifications.notifications(); track toast.id) {
        <div class="toast toast--{{ toast.kind }}">
          <app-icon class="toast__icon" [name]="iconFor(toast.kind)" [size]="18" />
          <span class="toast__message">{{ toast.message }}</span>
          <button
            type="button"
            class="toast__close"
            aria-label="Cerrar aviso"
            (click)="notifications.dismiss(toast.id)"
          >
            <app-icon name="close" [size]="15" />
          </button>
        </div>
      }
    </div>
  `,
  styles: `
    .toasts {
      position: fixed;
      right: var(--sp-4);
      bottom: var(--sp-4);
      z-index: 80;
      display: flex;
      flex-direction: column;
      gap: var(--sp-2);
      width: min(380px, calc(100vw - 2 * var(--sp-4)));
    }

    .toast {
      display: flex;
      align-items: flex-start;
      gap: var(--sp-3);
      padding: var(--sp-3) var(--sp-4);
      border-radius: var(--radius-sm);
      border-left: 4px solid var(--c-text-subtle);
      background: var(--c-surface);
      box-shadow: var(--shadow);
      animation: slide-in 0.18s ease-out;
    }

    .toast__icon {
      margin-top: 1px;
    }

    .toast--success {
      border-left-color: var(--c-success);

      .toast__icon {
        color: var(--c-success);
      }
    }

    .toast--error {
      border-left-color: var(--c-danger);

      .toast__icon {
        color: var(--c-danger);
      }
    }

    .toast--info {
      border-left-color: var(--c-primary);

      .toast__icon {
        color: var(--c-primary);
      }
    }

    .toast__message {
      flex: 1;
      font-size: 0.9rem;
    }

    .toast__close {
      display: inline-flex;
      padding: 2px;
      border: none;
      border-radius: var(--radius-sm);
      background: none;
      color: var(--c-text-subtle);
      cursor: pointer;

      &:hover {
        background: var(--c-surface-alt);
        color: var(--c-text);
      }
    }

    @keyframes slide-in {
      from {
        opacity: 0;
        transform: translateY(8px);
      }
    }
  `,
})
export class ToastHostComponent {
  protected readonly notifications = inject(NotificationService);

  protected iconFor(kind: NotificationKind): IconName {
    switch (kind) {
      case 'success':
        return 'check-circle';
      case 'error':
        return 'alert-circle';
      default:
        return 'info-circle';
    }
  }
}
