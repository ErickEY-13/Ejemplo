/**
 * Configuración de DESARROLLO.
 *
 * `apiUrl` es relativa a propósito: el proxy del dev-server (proxy.conf.js)
 * reenvía /api al backend, igual que hace Nginx en producción.
 */
export const environment = {
  production: false,
  appName: 'Ejemplo',
  apiUrl: '/api',
};
