import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed, toObservable, toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject, of } from 'rxjs';
import { catchError, debounceTime, distinctUntilChanged, switchMap, tap } from 'rxjs/operators';
import { ButtonModule } from 'primeng/button';
import { CardModule } from 'primeng/card';
import { CheckboxModule } from 'primeng/checkbox';
import { InputNumberModule } from 'primeng/inputnumber';
import { InputTextModule } from 'primeng/inputtext';
import { SelectModule } from 'primeng/select';
import { TableModule } from 'primeng/table';
import { TagModule } from 'primeng/tag';
import { TooltipModule } from 'primeng/tooltip';

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
import { AssignmentService } from '../../../assignments/services/assignment.service';
import { Site, VehicleAssignment } from '../../../assignments/models/assignment.model';

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
    ButtonModule,
    CardModule,
    CheckboxModule,
    InputNumberModule,
    InputTextModule,
    SelectModule,
    TableModule,
    TagModule,
    TooltipModule,
  ],
  templateUrl: './vehicle-list.page.html',
  styleUrl: './vehicle-list.page.scss',
})
export class VehicleListPage {
  private readonly vehicles = inject(VehicleService);
  private readonly confirm = inject(ConfirmService);
  private readonly notifications = inject(NotificationService);
  private readonly assignments = inject(AssignmentService);

  private readonly VIEW_MODE_KEY = 'vehicles-view-mode';
  protected readonly viewMode = signal<'table' | 'grid'>(
    (localStorage.getItem(this.VIEW_MODE_KEY) as 'table' | 'grid') ?? 'table'
  );

  protected setViewMode(mode: 'table' | 'grid'): void {
    this.viewMode.set(mode);
    localStorage.setItem(this.VIEW_MODE_KEY, mode);
  }

  protected readonly siteFilter = signal<number | null>(null);

  protected readonly sites = toSignal(
    this.assignments.getSites().pipe(catchError(() => of([] as Site[]))),
    { initialValue: [] as Site[] },
  );

  protected readonly siteOptions = computed(() => [
    { value: null, label: 'Todas' },
    ...this.sites().map((site) => ({ value: site.id, label: site.name })),
  ]);

  /** Asignaciones vigentes; con filtro de sede activo, ya vienen acotadas a esa sede. */
  private readonly currentAssignments = toSignal(
    toObservable(this.siteFilter).pipe(
      switchMap((siteId) =>
        this.assignments.listCurrent(siteId ?? undefined).pipe(catchError(() => of([] as VehicleAssignment[]))),
      ),
    ),
    { initialValue: [] as VehicleAssignment[] },
  );

  /** vehicle_id -> asignación vigente, para pintar sede/responsable en cada fila. */
  protected readonly assignmentByVehicle = computed(() => {
    const map = new Map<number, VehicleAssignment>();

    for (const assignment of this.currentAssignments()) {
      map.set(assignment.vehicle_id, assignment);
    }

    return map;
  });

  /**
   * Filtrado en cliente sobre la página actual: ver nota de diseño al inicio
   * del Task 7 del plan de trazabilidad de vehículos.
   */
  protected readonly visibleItems = computed(() => {
    const siteId = this.siteFilter();

    if (siteId === null) {
      return this.items();
    }

    const assignedVehicleIds = new Set(this.currentAssignments().map((a) => a.vehicle_id));

    return this.items().filter((vehicle) => assignedVehicleIds.has(vehicle.id));
  });

  protected onSiteFilterChange(siteId: number | null): void {
    this.siteFilter.set(siteId);
  }

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
      f.with_trashed ||
      this.siteFilter() !== null
    );
  });

  protected readonly metadata = toSignal(
    this.vehicles.metadata().pipe(catchError(() => of(null as VehicleMetadata | null))),
    { initialValue: null },
  );

  protected readonly typeOptions = computed(() => [
    { value: '', label: 'Todos' },
    ...(this.metadata()?.types ?? []),
  ]);
  protected readonly fuelTypeOptions = computed(() => [
    { value: '', label: 'Todos' },
    ...(this.metadata()?.fuel_types ?? []),
  ]);
  protected readonly statusOptions = [
    { value: '', label: 'Todos' },
    { value: '1', label: 'Activos' },
    { value: '0', label: 'Inactivos' },
  ];

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

  /**
   * `year_from`/`year_to` se guardan como string en `VehicleFilters`, pero
   * `p-inputnumber` trabaja con `number | null`: convierte en el borde.
   */
  protected onYearChange(field: 'year_from' | 'year_to', value: number | null): void {
    this.patchFilters({ [field]: value === null ? '' : String(value) });
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
    this.siteFilter.set(null);
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

  // ---------------------------------------------------------------- Exportación
  protected readonly exportingExcel = signal(false);
  protected readonly exportingPdf = signal(false);

  protected exportExcel(): void {
    this.exportingExcel.set(true);
    this.vehicles.exportExcel(this.filters()).subscribe({
      next: (blob) => {
        this.downloadBlob(blob, 'Flota.xlsx');
        this.exportingExcel.set(false);
        this.notifications.success('Excel exportado correctamente.');
      },
      error: () => {
        this.exportingExcel.set(false);
        this.notifications.error('Error al exportar Excel.');
      },
    });
  }

  protected exportPdf(): void {
    this.exportingPdf.set(true);
    this.vehicles.exportPdf(this.filters()).subscribe({
      next: (blob) => {
        this.downloadBlob(blob, 'Reporte_Vehiculos.pdf');
        this.exportingPdf.set(false);
        this.notifications.success('PDF exportado correctamente.');
      },
      error: () => {
        this.exportingPdf.set(false);
        this.notifications.error('Error al exportar PDF.');
      },
    });
  }

  private downloadBlob(blob: Blob, filename: string): void {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }
}
