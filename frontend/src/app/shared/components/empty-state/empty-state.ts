import { ChangeDetectionStrategy, Component, input } from '@angular/core';

import { IconComponent, IconName } from '../icon/icon';

/** Mensaje para listados vacíos o búsquedas sin resultados. */
@Component({
  selector: 'app-empty-state',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [IconComponent],
  template: `
    <div class="empty">
      <span class="empty__icon">
        <app-icon [name]="icon()" [size]="26" />
      </span>
      <h3 class="empty__title">{{ title() }}</h3>
      @if (description()) {
        <p class="empty__text">{{ description() }}</p>
      }
      <ng-content />
    </div>
  `,
  styles: `
    .empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: var(--sp-3);
      padding: var(--sp-7) var(--sp-4);
      text-align: center;
    }

    .empty__icon {
      display: grid;
      place-items: center;
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: var(--c-surface-alt);
      color: var(--c-text-subtle);
    }

    .empty__title {
      font-size: 1.05rem;
    }

    .empty__text {
      max-width: 44ch;
      margin: 0;
      color: var(--c-text-muted);
      font-size: 0.92rem;
    }
  `,
})
export class EmptyStateComponent {
  readonly icon = input<IconName>('search');
  readonly title = input.required<string>();
  readonly description = input<string | null>(null);
}
