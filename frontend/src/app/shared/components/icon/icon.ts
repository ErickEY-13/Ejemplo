import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

/**
 * Nombres de icono disponibles. Al añadir uno nuevo, súmalo aquí y añade su
 * `@case` correspondiente en la plantilla.
 */
export type IconName =
  | 'logo'
  | 'home'
  | 'users'
  | 'car'
  | 'search'
  | 'plus'
  | 'pencil'
  | 'trash'
  | 'restore'
  | 'arrow-left'
  | 'arrow-right'
  | 'chevron-left'
  | 'chevron-right'
  | 'chevron-up'
  | 'chevron-down'
  | 'check-circle'
  | 'alert-circle'
  | 'info-circle'
  | 'alert-triangle'
  | 'close';

/**
 * Iconos vectoriales dibujados a mano, sin librerías externas.
 *
 * Se trazan con `currentColor`, así que heredan el color del texto que los
 * rodea y funcionan igual sobre cualquier fondo. Frente a los emoji: se ven
 * idénticos en todos los sistemas operativos, escalan sin pixelarse y se
 * pueden colorear.
 *
 * ```html
 * <app-icon name="users" [size]="20" />
 * <app-icon name="trash" label="Eliminar" />
 * ```
 */
@Component({
  selector: 'app-icon',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      [attr.stroke-width]="strokeWidth()"
      stroke-linecap="round"
      stroke-linejoin="round"
      [attr.width]="size()"
      [attr.height]="size()"
      [attr.role]="label() ? 'img' : null"
      [attr.aria-label]="label()"
      [attr.aria-hidden]="label() ? null : 'true'"
      [attr.focusable]="false"
    >
      @switch (name()) {
        @case ('logo') {
          <path d="M12 2.8 21.2 12 12 21.2 2.8 12 12 2.8Z" />
          <path d="M12 8.2 15.8 12 12 15.8 8.2 12 12 8.2Z" />
        }
        @case ('home') {
          <path d="m3 10.8 9-6.9 9 6.9" />
          <path d="M5.6 9.6V20h12.8V9.6" />
          <path d="M9.9 20v-5.1h4.2V20" />
        }
        @case ('users') {
          <circle cx="12" cy="8" r="3.8" />
          <path d="M4.8 20.4v-1.2a5.4 5.4 0 0 1 5.4-5.4h3.6a5.4 5.4 0 0 1 5.4 5.4v1.2" />
        }
        @case ('car') {
          <path d="M6.6 6.4h10.8l2.6 6.1H4L6.6 6.4Z" />
          <path d="M2.8 12.5h18.4v4.6H2.8z" />
          <circle cx="7.2" cy="17.1" r="1.8" />
          <circle cx="16.8" cy="17.1" r="1.8" />
        }
        @case ('search') {
          <circle cx="10.8" cy="10.8" r="6.2" />
          <path d="m20 20-4.8-4.8" />
        }
        @case ('plus') {
          <path d="M12 5.2v13.6" />
          <path d="M5.2 12h13.6" />
        }
        @case ('pencil') {
          <path d="M4 20h4.2L19.3 8.9a2.2 2.2 0 0 0-3.1-3.1L5.1 16.9 4 20Z" />
          <path d="m14.6 6.6 3.1 3.1" />
        }
        @case ('trash') {
          <path d="M4.4 6.9h15.2" />
          <path d="M9.5 6.9V5.6A1.6 1.6 0 0 1 11.1 4h1.8a1.6 1.6 0 0 1 1.6 1.6v1.3" />
          <path d="M6.4 6.9v11.5A1.9 1.9 0 0 0 8.3 20h7.4a1.9 1.9 0 0 0 1.9-1.6V6.9" />
          <path d="M10.3 10.8v5.3" />
          <path d="M13.7 10.8v5.3" />
        }
        @case ('restore') {
          <path d="M3.8 12a8.2 8.2 0 1 0 2.5-5.9L3.4 8.9" />
          <path d="M3.4 4.2v4.7h4.7" />
        }
        @case ('arrow-left') {
          <path d="M19 12H5.4" />
          <path d="m11 5.6-5.6 6.4 5.6 6.4" />
        }
        @case ('arrow-right') {
          <path d="M5 12h13.6" />
          <path d="m13 5.6 5.6 6.4-5.6 6.4" />
        }
        @case ('chevron-left') {
          <path d="m14.4 5.6-6.4 6.4 6.4 6.4" />
        }
        @case ('chevron-right') {
          <path d="m9.6 5.6 6.4 6.4-6.4 6.4" />
        }
        @case ('chevron-up') {
          <path d="m5.6 14.9 6.4-6.4 6.4 6.4" />
        }
        @case ('chevron-down') {
          <path d="m5.6 9.1 6.4 6.4 6.4-6.4" />
        }
        @case ('check-circle') {
          <circle cx="12" cy="12" r="8.4" />
          <path d="m8.4 12.2 2.6 2.6 4.6-5.2" />
        }
        @case ('alert-circle') {
          <circle cx="12" cy="12" r="8.4" />
          <path d="M12 7.8v4.8" />
          <path d="M12 16.1h.01" />
        }
        @case ('info-circle') {
          <circle cx="12" cy="12" r="8.4" />
          <path d="M12 11.2v5" />
          <path d="M12 7.9h.01" />
        }
        @case ('alert-triangle') {
          <path d="M12 4.2 21.2 19.8H2.8L12 4.2Z" />
          <path d="M12 9.8v4.4" />
          <path d="M12 17.4h.01" />
        }
        @case ('close') {
          <path d="m6.4 6.4 11.2 11.2" />
          <path d="M17.6 6.4 6.4 17.6" />
        }
      }
    </svg>
  `,
  styles: `
    :host {
      display: inline-flex;
      flex: none;
      line-height: 0;
    }
  `,
})
export class IconComponent {
  readonly name = input.required<IconName>();

  /** Lado del icono en píxeles. */
  readonly size = input(20);

  /**
   * Texto alternativo. Si se omite, el icono se marca como decorativo
   * (`aria-hidden`), que es lo correcto cuando va junto a una etiqueta visible.
   */
  readonly label = input<string | null>(null);

  /** Los iconos pequeños necesitan un trazo algo más grueso para no perderse. */
  protected readonly strokeWidth = computed(() => (this.size() <= 16 ? 1.9 : 1.7));
}
