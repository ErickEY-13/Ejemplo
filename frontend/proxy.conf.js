/**
 * Proxy del servidor de desarrollo de Angular.
 *
 * Redirige /api hacia el backend de Laravel para que, también en desarrollo,
 * el SPA y la API compartan origen: la configuración es idéntica a la de
 * producción y no hay que lidiar con CORS.
 *
 * Dentro de Docker el destino es el contenedor `nginx`; si alguien levanta
 * `npm start` fuera de Docker, se usa localhost:8000.
 */
const target = process.env['API_PROXY_TARGET'] || 'http://localhost:8000';

module.exports = {
  '/api': {
    target,
    changeOrigin: true,
    secure: false,
  },
  '/storage': {
    target,
    changeOrigin: true,
    secure: false,
  },
};
