/**
 * Configuración de PRODUCCIÓN.
 *
 * Nginx sirve el SPA y la API desde el mismo origen, así que la ruta relativa
 * funciona sea cual sea el dominio del Droplet.
 */
export const environment = {
  production: true,
  appName: 'Ejemplo',
  apiUrl: '/api',
};
