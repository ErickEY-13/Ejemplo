import { IconName } from '../../shared/components/icon/icon';

/**
 * Catálogo de módulos que alimenta el menú principal y la barra superior.
 *
 * Añadir un módulo nuevo se reduce a sumar una entrada aquí y su archivo de
 * rutas: ni el shell ni el menú necesitan más cambios.
 */
export interface AppModule {
  key: string;
  name: string;
  description: string;
  path: string;
  /** Icono del módulo (ver shared/components/icon). */
  icon: IconName;
  /** Responsable del módulo, tal y como se acordó en el reparto del equipo. */
  owner: string;
}

export const APP_MODULES: readonly AppModule[] = [
  {
    key: 'persons',
    name: 'Registro de Personas',
    description: 'Alta, búsqueda y mantenimiento de los datos de las personas.',
    path: '/personas',
    icon: 'users',
    owner: 'Desarrollador 1',
  },
  {
    key: 'vehicles',
    name: 'Registro de Vehículos',
    description: 'Alta, búsqueda y mantenimiento de la flota de vehículos.',
    path: '/vehiculos',
    icon: 'car',
    owner: 'Desarrollador 2',
  },
];
