import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { ConfirmDialogModule } from 'primeng/confirmdialog';
import { ToastModule } from 'primeng/toast';

import { environment } from '../environments/environment';
import { APP_MODULES } from './core/navigation/modules';
import { IconComponent } from './shared/components/icon/icon';

/**
 * Shell de la aplicación: cabecera con la navegación entre módulos, el hueco
 * donde se pintan las páginas y los servicios visuales globales (avisos y
 * diálogo de confirmación), servidos por `p-toast` y `p-confirmdialog`.
 */
@Component({
  selector: 'app-root',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, IconComponent, ToastModule, ConfirmDialogModule],
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App {
  protected readonly appName = environment.appName;
  protected readonly modules = APP_MODULES;
}
