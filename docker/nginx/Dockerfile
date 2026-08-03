# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage: frontend-build
# Compila la aplicación Angular en modo producción.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS frontend-build

WORKDIR /app

ENV NG_CLI_ANALYTICS=false

COPY frontend/package.json frontend/package-lock.json frontend/.npmrc ./
RUN npm ci --legacy-peer-deps

COPY frontend/ ./
RUN npm run build -- --configuration production


# ---------------------------------------------------------------------------
# Stage: production
# Nginx sirve el build de Angular y hace de proxy FastCGI hacia PHP-FPM.
# ---------------------------------------------------------------------------
FROM nginx:1.29-alpine AS production

# Estáticos de Angular
COPY --from=frontend-build /app/dist/frontend/browser /usr/share/nginx/html

# El directorio public/ de Laravel se necesita para resolver rutas y assets;
# el PHP en sí lo ejecuta el contenedor `app` vía FastCGI.
COPY backend/public /var/www/html/public

COPY docker/nginx/conf.d/prod.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/snippets/ /etc/nginx/snippets/

EXPOSE 80
