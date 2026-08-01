import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

import { IconComponent } from '../../shared/components/icon/icon';

@Component({
  selector: 'app-not-found',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, IconComponent],
  template: `
    <div class="card not-found">
      <span class="not-found__icon">
        <app-icon name="alert-triangle" [size]="28" />
      </span>
      <span class="not-found__code">404</span>
      <h1>Página no encontrada</h1>
      <p class="text-muted">La dirección a la que intentas acceder no existe o ha cambiado.</p>
      <a class="btn btn--primary" routerLink="/">
        <app-icon name="arrow-left" [size]="16" />
        Volver al menú principal
      </a>
    </div>
  `,
  styles: `
    .not-found {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: var(--sp-3);
      padding: var(--sp-7) var(--sp-5);
      text-align: center;
    }

    .not-found__icon {
      display: grid;
      place-items: center;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: var(--c-warning-soft);
      color: var(--c-warning);
    }

    .not-found__code {
      font-size: 2.4rem;
      font-weight: 700;
      color: var(--c-primary);
      line-height: 1;
    }
  `,
})
export class NotFoundPage {}
