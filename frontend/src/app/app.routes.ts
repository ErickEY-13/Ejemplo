import { Routes } from '@angular/router';

/*
 * Rutas de primer nivel.
 *
 * Cada módulo se carga de forma perezosa (lazy loading) desde su propio
 * archivo de rutas, así que su código no entra en el bundle inicial y los dos
 * desarrolladores tocan archivos distintos. Este archivo es de los pocos
 * compartidos: solo cambia al dar de alta un módulo nuevo.
 */
export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    title: 'Menú principal',
    loadComponent: () => import('./features/home/home.page').then((m) => m.HomePage),
  },
  {
    // Módulo 1 — Desarrollador 1
    path: 'personas',
    loadChildren: () => import('./features/persons/persons.routes').then((m) => m.PERSONS_ROUTES),
  },
  {
    // Módulo 2 — Desarrollador 2
    path: 'vehiculos',
    loadChildren: () => import('./features/vehicles/vehicles.routes').then((m) => m.VEHICLES_ROUTES),
  },
  {
    path: '**',
    title: 'Página no encontrada',
    loadComponent: () => import('./features/not-found/not-found.page').then((m) => m.NotFoundPage),
  },
];
