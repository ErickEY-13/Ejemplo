import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { environment } from '../environments/environment';
import { APP_MODULES } from './core/navigation/modules';
import { ConfirmDialogComponent } from './shared/components/confirm-dialog/confirm-dialog';
import { IconComponent } from './shared/components/icon/icon';
import { ToastHostComponent } from './shared/components/toast-host/toast-host';

/**
 * Shell de la aplicación: cabecera con la navegación entre módulos, el hueco
 * donde se pintan las páginas y los servicios visuales globales (avisos y
 * diálogo de confirmación).
 */
@Component({
  selector: 'app-root',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    RouterOutlet,
    RouterLink,
    RouterLinkActive,
    IconComponent,
    ToastHostComponent,
    ConfirmDialogComponent,
  ],
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App {
  protected readonly appName = environment.appName;
  protected readonly modules = APP_MODULES;
}
