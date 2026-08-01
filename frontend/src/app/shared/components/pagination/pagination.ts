import { ChangeDetectionStrategy, Component, computed, input, output } from '@angular/core';

import { PaginationMeta } from '../../../core/api/api.types';
import { IconComponent } from '../icon/icon';

/**
 * Paginador reutilizable. Recibe el `meta` que devuelve Laravel y emite el
 * número de página solicitado.
 */
@Component({
  selector: 'app-pagination',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [IconComponent],
  template: `
    @if (meta().total > 0) {
      <nav class="pagination" aria-label="Paginación">
        <p class="pagination__summary">
          Mostrando <strong>{{ meta().from }}</strong> – <strong>{{ meta().to }}</strong>
          de <strong>{{ meta().total }}</strong> registros
        </p>

        <div class="pagination__controls">
          <button
            type="button"
            class="btn btn--ghost btn--sm"
            [disabled]="isFirst()"
            (click)="pageChange.emit(meta().current_page - 1)"
          >
            <app-icon name="chevron-left" [size]="15" />
            Anterior
          </button>

          @for (page of pages(); track page) {
            @if (page === null) {
              <span class="pagination__gap" aria-hidden="true">…</span>
            } @else {
              <button
                type="button"
                class="btn btn--sm"
                [class.btn--primary]="page === meta().current_page"
                [class.btn--ghost]="page !== meta().current_page"
                [attr.aria-current]="page === meta().current_page ? 'page' : null"
                (click)="pageChange.emit(page)"
              >
                {{ page }}
              </button>
            }
          }

          <button
            type="button"
            class="btn btn--ghost btn--sm"
            [disabled]="isLast()"
            (click)="pageChange.emit(meta().current_page + 1)"
          >
            Siguiente
            <app-icon name="chevron-right" [size]="15" />
          </button>
        </div>
      </nav>
    }
  `,
  styleUrl: './pagination.scss',
})
export class PaginationComponent {
  readonly meta = input.required<PaginationMeta>();
  readonly pageChange = output<number>();

  protected readonly isFirst = computed(() => this.meta().current_page <= 1);
  protected readonly isLast = computed(() => this.meta().current_page >= this.meta().last_page);

  /**
   * Ventana de páginas alrededor de la actual. Los `null` son los puntos
   * suspensivos entre bloques.
   */
  protected readonly pages = computed<(number | null)[]>(() => {
    const { current_page: current, last_page: last } = this.meta();

    if (last <= 7) {
      return Array.from({ length: last }, (_, index) => index + 1);
    }

    const window = new Set<number>([1, last, current]);

    for (const offset of [-1, 1]) {
      const page = current + offset;
      if (page > 1 && page < last) {
        window.add(page);
      }
    }

    const sorted = [...window].sort((a, b) => a - b);
    const result: (number | null)[] = [];

    sorted.forEach((page, index) => {
      if (index > 0 && page - sorted[index - 1] > 1) {
        result.push(null);
      }
      result.push(page);
    });

    return result;
  });
}
