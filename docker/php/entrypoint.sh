#!/bin/bash
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

log() { echo "[entrypoint] $*"; }

# ---------------------------------------------------------------------------
# 0. ¿Con qué usuario se ejecuta la aplicación?
#
#    En producción la imagen ya arranca como www-data y no hay nada que decidir.
#    En desarrollo el código llega por bind mount, y no todos los hosts respetan
#    los permisos POSIX: Docker Desktop en Windows expone los archivos como
#    root:root 0644, así que www-data no podría escribir en el proyecto.
#    Detectamos el caso y caemos a root solo cuando hace falta.
# ---------------------------------------------------------------------------
RUN_USER=www-data

if [ "$(id -u)" = "0" ]; then
    if ! su-exec www-data test -w "$APP_DIR"; then
        log "El bind mount no es escribible por www-data (host sin permisos POSIX): se usará root."
        RUN_USER=root
    fi

    # PHP-FPM necesita saber con qué usuario levantar los workers.
    printf '[www]\nuser = %s\ngroup = %s\n' "$RUN_USER" "$RUN_USER" \
        > /usr/local/etc/php-fpm.d/zz-runtime.conf

    as_app() { su-exec "$RUN_USER" "$@"; }
else
    as_app() { "$@"; }
fi

# ---------------------------------------------------------------------------
# 1. Archivo .env (solo desarrollo; en producción las vars vienen del compose)
# ---------------------------------------------------------------------------
if [ "${APP_ENV:-local}" != "production" ] && [ ! -f .env ] && [ -f .env.example ]; then
    log "Creando .env a partir de .env.example"
    as_app cp .env.example .env
fi

# ---------------------------------------------------------------------------
# 2. Dependencias de Composer
#    Se reinstalan solas cuando composer.lock cambia (tras un git pull).
# ---------------------------------------------------------------------------
if [ "${APP_ENV:-local}" != "production" ]; then
    as_app mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

    LOCK_HASH_FILE="storage/framework/.composer.lock.md5"
    CURRENT_HASH=$(md5sum composer.lock 2>/dev/null | cut -d' ' -f1 || echo "none")

    if [ ! -f vendor/autoload.php ] || [ "$(cat "$LOCK_HASH_FILE" 2>/dev/null)" != "$CURRENT_HASH" ]; then
        log "Instalando dependencias de Composer..."
        as_app composer install --no-interaction --prefer-dist
        echo "$CURRENT_HASH" | as_app tee "$LOCK_HASH_FILE" > /dev/null
    else
        log "Dependencias de Composer al día."
    fi
fi

# ---------------------------------------------------------------------------
# 3. Esperar a PostgreSQL
# ---------------------------------------------------------------------------
if [ -n "${DB_HOST:-}" ]; then
    log "Esperando a PostgreSQL en ${DB_HOST}:${DB_PORT:-5432}..."
    for i in $(seq 1 60); do
        if pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -q; then
            log "PostgreSQL disponible."
            break
        fi
        [ "$i" -eq 60 ] && log "AVISO: PostgreSQL no respondió en 60s, se continúa igualmente."
        sleep 1
    done
fi

# ---------------------------------------------------------------------------
# 4. APP_KEY
# ---------------------------------------------------------------------------
if [ -z "${APP_KEY:-}" ] && ! grep -qE '^APP_KEY=base64:' .env 2>/dev/null; then
    log "Generando APP_KEY..."
    as_app php artisan key:generate --force --no-interaction || true
fi

# ---------------------------------------------------------------------------
# 5. Migraciones automáticas (opt-in vía AUTO_MIGRATE=true)
# ---------------------------------------------------------------------------
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    log "Ejecutando migraciones..."
    as_app php artisan migrate --force --no-interaction || log "AVISO: las migraciones fallaron."
fi

# ---------------------------------------------------------------------------
# 6. Cachés del framework
# ---------------------------------------------------------------------------
as_app php artisan storage:link --no-interaction > /dev/null 2>&1 || true

if [ "${APP_ENV:-local}" = "production" ]; then
    log "Cacheando configuración, rutas y vistas..."
    as_app php artisan config:cache --no-interaction || true
    as_app php artisan route:cache --no-interaction || true
else
    as_app php artisan config:clear --no-interaction > /dev/null 2>&1 || true
    as_app php artisan route:clear --no-interaction > /dev/null 2>&1 || true
fi

log "Listo. Ejecutando: $*"
exec "$@"
