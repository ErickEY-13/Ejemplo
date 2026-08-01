import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

import { environment } from '../../../environments/environment';
import { APP_MODULES } from '../../core/navigation/modules';
import { IconComponent } from '../../shared/components/icon/icon';

/**
 * Menú principal: punto de entrada que divide el sistema en sus módulos.
 */
@Component({
  selector: 'app-home',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, IconComponent],
  templateUrl: './home.page.html',
  styleUrl: './home.page.scss',
})
export class HomePage {
  protected readonly appName = environment.appName;
  protected readonly modules = APP_MODULES;
}
