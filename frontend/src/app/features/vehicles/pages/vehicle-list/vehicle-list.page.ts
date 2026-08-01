import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed, toObservable, toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject, of } from 'rxjs';
import { catchError, debounceTime, distinctUntilChanged, switchMap, tap } from 'rxjs/operators';

import { EMPTY_META, Paginated } from '../../../../core/api/api.types';
import { NotificationService } from '../../../../core/notifications/notification.service';
import { ConfirmService } from '../../../../shared/components/confirm-dialog/confirm.service';
import { EmptyStateComponent } from '../../../../shared/components/empty-state/empty-state';
import { IconComponent, IconName } from '../../../../shared/components/icon/icon';
import { PaginationComponent } from '../../../../shared/components/pagination/pagination';
import { SpinnerComponent } from '../../../../shared/components/spinner/spinner';
import {
  DEFAULT_VEHICLE_FILTERS,
  Vehicle,
  VehicleFilters,
  VehicleMetadata,
} from '../../models/vehicle.model';
import { VehicleService } from '../../services/vehicle.service';

const EMPTY_PAGE: Paginated<Vehicle> = { data: [], meta: EMPTY_META };

@Component({
  selector: 'app-vehicle-list',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    FormsModule,
    RouterLink,
    IconComponent,
    PaginationComponent,
    SpinnerComponent,
    EmptyStateComponent,
  ],
  templateUrl: './vehicle-list.page.html',
  styleUrl: './vehicle-list.page.scss',
})
export class VehicleListPage {
  private readonly vehicles = inject(VehicleService);
  private readonly confirm = inject(ConfirmService);
  private readonly notifications = inject(NotificationService);

  /** Filtros activos; cualquier cambio dispara una nueva consulta. */
  protected readonly filters = signal<VehicleFilters>({ ...DEFAULT_VEHICLE_FILTERS });
  protected readonly loading = signal(false);

  /** Se incrementa para forzar una recarga con los mismos filtros. */
  private readonly refreshToken = signal(0);

  private readonly query = computed(() => ({
    filters: this.filters(),
    token: this.refreshToken(),
  }));

  private readonly page = toSignal(
    toObservable(this.query).pipe(
      tap(() => this.loading.set(true)),
      switchMap(({ filters }) => this.vehicles.list(filters).pipe(catchError(() => of(EMPTY_PAGE)))),
      tap(() => this.loading.set(false)),
    ),
    { initialValue: EMPTY_PAGE },
  );

  protected readonly items = computed(() => this.page().data);
  protected readonly meta = computed(() => this.page().meta);
  protected readonly hasFilters = computed(() => {
    const f = this.filters();
    return (
      f.search !== '' ||
      f.type !== '' ||
      f.fuel_type !== '' ||
      f.brand !== '' ||
      f.year_from !== '' ||
      f.year_to !== '' ||
      f.is_active !== '' ||
      f.with_trashed
    );
  });

  protected readonly metadata = toSignal(
    this.vehicles.metadata().pipe(catchError(() => of(null as VehicleMetadata | null))),
    { initialValue: null },
  );

  /** Texto del buscador, con retardo para no lanzar una petición por tecla. */
  private readonly searchTerm = new Subject<string>();

  constructor() {
    this.searchTerm
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed())
      .subscribe((search) => this.patchFilters({ search }));
  }

  protected onSearch(value: string): void {
    this.searchTerm.next(value);
  }

  /** Aplica cambios de filtro y vuelve siempre a la primera página. */
  protected patchFilters(patch: Partial<VehicleFilters>): void {
    this.filters.update((current) => ({ ...current, ...patch, page: patch.page ?? 1 }));
  }

  protected goToPage(page: number): void {
    this.filters.update((current) => ({ ...current, page }));
  }

  protected sortBy(column: string): void {
    this.filters.update((current) => ({
      ...current,
      sort: column,
      direction: current.sort === column && current.direction === 'asc' ? 'desc' : 'asc',
      page: 1,
    }));
  }

  /** Flecha de orden de la columna, o `null` si no es la que ordena. */
  protected sortIcon(column: string): IconName | null {
    const { sort, direction } = this.filters();

    if (sort !== column) {
      return null;
    }

    return direction === 'asc' ? 'chevron-up' : 'chevron-down';
  }

  protected resetFilters(): void {
    this.filters.set({ ...DEFAULT_VEHICLE_FILTERS });
  }

  protected async remove(vehicle: Vehicle): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Eliminar vehículo',
      message: `¿Seguro que quieres eliminar el vehículo de placa ${vehicle.plate}? Podrás restaurarlo más adelante.`,
      confirmLabel: 'Eliminar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.vehicles.remove(vehicle.id).subscribe({
      next: () => {
        this.notifications.success(`El vehículo ${vehicle.plate} se eliminó correctamente.`);
        this.reload();
      },
    });
  }

  protected restore(vehicle: Vehicle): void {
    this.vehicles.restore(vehicle.id).subscribe({
      next: () => {
        this.notifications.success(`El vehículo ${vehicle.plate} se restauró correctamente.`);
        this.reload();
      },
    });
  }

  private reload(): void {
    this.refreshToken.update((value) => value + 1);
  }
}
