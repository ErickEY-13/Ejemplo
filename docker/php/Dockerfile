# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage: base
# Imagen común para desarrollo y producción. Contiene PHP-FPM + extensiones.
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_HOME=/tmp/composer

# Dependencias de sistema + extensiones PHP requeridas por Laravel/PostgreSQL
RUN apk add --no-cache \
        bash \
        curl \
        git \
        unzip \
        tzdata \
        su-exec \
        icu-data-full \
        postgresql-client \
        libpq \
        libzip \
        icu-libs \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        linux-headers \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        bcmath \
        opcache \
        pcntl \
        sockets \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Composer (copiado desde la imagen oficial, evita instalarlo a mano)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Alinea el usuario www-data del contenedor con el UID/GID del host, para que
# los archivos creados sobre los bind mounts no queden como root (Linux/DO).
RUN apk add --no-cache shadow \
    && groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" -s /bin/bash www-data \
    && apk del shadow

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]


# ---------------------------------------------------------------------------
# Stage: development
# El código llega por bind mount (docker-compose.yml), no se copia a la imagen.
# ---------------------------------------------------------------------------
FROM base AS development

ENV APP_ENV=local

COPY docker/php/conf.d/php.dev.ini /usr/local/etc/php/conf.d/zz-app.ini

# El contenedor de desarrollo arranca como root a propósito: el entrypoint
# comprueba si el bind mount admite permisos POSIX y baja los workers a
# www-data cuando puede (Linux). `-R` autoriza a FPM a correr como root en los
# hosts donde no puede (Docker Desktop en Windows expone todo como root:root).
CMD ["php-fpm", "-R"]


# ---------------------------------------------------------------------------
# Stage: vendor
# Instala dependencias de Composer en una capa cacheable.
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist


# ---------------------------------------------------------------------------
# Stage: production
# Imagen autocontenida: incluye el código y las dependencias ya instaladas.
# ---------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production

COPY docker/php/conf.d/php.prod.ini /usr/local/etc/php/conf.d/zz-app.ini

COPY --from=vendor /var/www/html/vendor ./vendor
COPY backend/ ./

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data
