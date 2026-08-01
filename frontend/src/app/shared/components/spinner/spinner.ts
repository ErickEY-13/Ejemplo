import { ChangeDetectionStrategy, Component, input } from '@angular/core';

/** Indicador de carga sencillo, sin dependencias externas. */
@Component({
  selector: 'app-spinner',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="spinner" role="status">
      <span class="spinner__circle" aria-hidden="true"></span>
      <span class="spinner__label">{{ label() }}</span>
    </div>
  `,
  styles: `
    .spinner {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: var(--sp-3);
      padding: var(--sp-7) var(--sp-4);
      color: var(--c-text-muted);
    }

    .spinner__circle {
      width: 26px;
      height: 26px;
      border: 3px solid var(--c-border);
      border-top-color: var(--c-primary);
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }

    .spinner__label {
      font-size: 0.9rem;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  `,
})
export class SpinnerComponent {
  readonly label = input('Cargando…');
}
