#!/bin/bash
set -e

cd /app

log() { echo "[entrypoint] $*"; }

# Reinstala dependencias solo cuando package-lock.json cambia (tras un git pull)
LOCK_HASH_FILE="node_modules/.package-lock.md5"
CURRENT_HASH=$(md5sum package-lock.json 2>/dev/null | cut -d' ' -f1 || echo "none")

if [ ! -d node_modules/@angular ] || [ "$(cat "$LOCK_HASH_FILE" 2>/dev/null)" != "$CURRENT_HASH" ]; then
    log "Instalando dependencias de npm (esto puede tardar unos minutos)..."
    if [ -f package-lock.json ]; then
        npm ci --legacy-peer-deps
    else
        npm install --legacy-peer-deps
    fi
    echo "$CURRENT_HASH" > "$LOCK_HASH_FILE"
else
    log "Dependencias de npm al día."
fi

log "Listo. Ejecutando: $*"
exec "$@"
