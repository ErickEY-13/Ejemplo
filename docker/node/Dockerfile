# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Entorno de desarrollo para Angular (ng serve).
# El código llega por bind mount; node_modules vive en un volumen nombrado
# para no penalizar el rendimiento del filesystem del host.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS development

RUN apk add --no-cache bash git

WORKDIR /app

ENV NG_CLI_ANALYTICS=false \
    CHOKIDAR_USEPOLLING=true

COPY docker/node/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 4200

ENTRYPOINT ["entrypoint"]
CMD ["npm", "run", "start:docker"]
