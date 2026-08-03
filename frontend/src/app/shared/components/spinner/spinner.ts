import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { ProgressSpinnerModule } from 'primeng/progressspinner';

/** Indicador de carga sencillo, apoyado en `p-progress-spinner`. */
@Component({
  selector: 'app-spinner',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ProgressSpinnerModule],
  template: `
    <div class="spinner" role="status">
      <p-progress-spinner styleClass="spinner__circle" strokeWidth="4" animationDuration="0.7s" />
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
      width: 32px;
      height: 32px;
    }

    .spinner__label {
      font-size: 0.9rem;
    }
  `,
})
export class SpinnerComponent {
  readonly label = input('Cargando…');
}
