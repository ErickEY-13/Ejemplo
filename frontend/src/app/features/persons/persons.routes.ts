import { Routes } from '@angular/router';

/*
 * Rutas del módulo de Personas (Desarrollador 1).
 *
 * Se montan bajo /personas desde app.routes.ts. Añadir pantallas aquí no
 * afecta a ningún otro módulo.
 */
export const PERSONS_ROUTES: Routes = [
  {
    path: '',
    title: 'Registro de Personas',
    loadComponent: () => import('./pages/person-list/person-list.page').then((m) => m.PersonListPage),
  },
  {
    path: 'nueva',
    title: 'Nueva persona',
    loadComponent: () => import('./pages/person-form/person-form.page').then((m) => m.PersonFormPage),
  },
  {
    path: ':id/editar',
    title: 'Editar persona',
    loadComponent: () => import('./pages/person-form/person-form.page').then((m) => m.PersonFormPage),
  },
];
