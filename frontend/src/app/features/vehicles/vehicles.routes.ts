import { Routes } from '@angular/router';

/*
 * Rutas del módulo de Vehículos (Desarrollador 2).
 *
 * Se montan bajo /vehiculos desde app.routes.ts. Añadir pantallas aquí no
 * afecta a ningún otro módulo.
 */
export const VEHICLES_ROUTES: Routes = [
  {
    path: '',
    title: 'Registro de Vehículos',
    loadComponent: () =>
      import('./pages/vehicle-list/vehicle-list.page').then((m) => m.VehicleListPage),
  },
  {
    path: 'nuevo',
    title: 'Nuevo vehículo',
    loadComponent: () =>
      import('./pages/vehicle-form/vehicle-form.page').then((m) => m.VehicleFormPage),
  },
  {
    path: ':id/editar',
    title: 'Editar vehículo',
    loadComponent: () =>
      import('./pages/vehicle-form/vehicle-form.page').then((m) => m.VehicleFormPage),
  },
  {
    path: ':id',
    title: 'Detalle del vehículo',
    loadComponent: () =>
      import('./pages/vehicle-detail/vehicle-detail.page').then((m) => m.VehicleDetailPage),
  },
];
