#!/usr/bin/env bash
#
# Despliegue en el Droplet.
#
#   ./docker/scripts/deploy.sh
#
# Trae los últimos cambios, reconstruye las imágenes, aplica las migraciones y
# comprueba que la aplicación responde. Si el arranque falla, deja el estado a
# la vista en lugar de terminar en silencio.
#
set -euo pipefail

cd "$(dirname "$0")/../.."

log()  { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }
fail() { printf '\n\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

[ -f .env ] || fail "No existe el archivo .env. Cópialo de .env.example y complétalo."

if ! grep -qE '^COMPOSE_FILE=.*docker-compose\.prod\.yml' .env; then
    fail "Falta 'COMPOSE_FILE=docker-compose.prod.yml' en .env (ver el README)."
fi

log "Descargando los últimos cambios"
git pull --ff-only

log "Reconstruyendo y levantando los contenedores"
docker compose up -d --build --remove-orphans

log "Esperando a que la aplicación arranque"
for attempt in $(seq 1 30); do
    if docker compose exec -T app php artisan --version > /dev/null 2>&1; then
        break
    fi
    [ "$attempt" -eq 30 ] && fail "El contenedor 'app' no llegó a arrancar. Revisa: docker compose logs app"
    sleep 2
done

log "Aplicando migraciones"
docker compose exec -T app php artisan migrate --force

log "Regenerando cachés"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache

log "Comprobando el estado de la aplicación"
HTTP_PORT=$(grep -E '^HTTP_PORT=' .env | cut -d= -f2 || true)
HEALTH_URL="http://localhost:${HTTP_PORT:-80}/api/health"

if curl -fsS --max-time 10 "$HEALTH_URL" > /dev/null; then
    log "Despliegue correcto. La aplicación responde en $HEALTH_URL"
else
    fail "La aplicación no responde en $HEALTH_URL. Revisa: docker compose logs --tail 100"
fi

log "Limpiando imágenes antiguas"
docker image prune -f > /dev/null

docker compose ps
