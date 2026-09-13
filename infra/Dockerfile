# RukunMuda production image.
#
# One image, three roles (web / queue / scheduler) selected by the
# container's command — they must run the *same* build, or a deploy can
# leave a worker executing last release's job classes.

# ---------------------------------------------------------------- assets
FROM node:22-alpine AS assets

WORKDIR /app
COPY laravel/package.json laravel/package-lock.json ./
RUN npm ci
COPY laravel/ ./
# Plain `build`, not `build:ssr` — Inertia SSR is off (no config/inertia.php),
# so building the SSR bundle would ship a server nothing runs.
# Ziggy needs no build step here: app.blade.php injects routes via @routes.
RUN npm run build

# -------------------------------------------------------------- composer
FROM composer:2 AS vendor

WORKDIR /app
COPY laravel/composer.json laravel/composer.lock ./
# --no-scripts: artisan isn't present yet, and package discovery runs in
# the runtime stage where the full app exists.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist
COPY laravel/ ./
RUN composer dump-autoload --optimize --classmap-authoritative

# --------------------------------------------------------------- runtime
FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache \
        postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        icu-dev libzip-dev oniguruma-dev $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    # gd: endroid/qr-code (attendance + report QR) and dompdf.
    # intl: Indonesian date/number formatting.
    # pdo_pgsql: the only supported production driver.
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql gd intl zip opcache exif \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY infra/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY infra/php/php.ini /usr/local/etc/php/conf.d/rukunmuda.ini

WORKDIR /app

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

# php-fpm runs as www-data; storage/ and bootstrap/cache/ are the only
# paths Laravel writes to.
RUN chown -R www-data:www-data storage bootstrap/cache

COPY infra/scripts/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
