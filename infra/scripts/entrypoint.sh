#!/bin/sh
# Container entrypoint, shared by the web, queue and scheduler roles.
set -e

# Publish the built public/ into the volume Caddy serves. Done on every
# boot, not via a named volume's first-creation copy: that copy happens
# once, so a redeployed image would keep serving the previous release's
# JS bundles against the new manifest — a white screen with a 404 for a
# hashed asset, and nothing in the app's own logs to explain it.
if [ -d /var/www/public ]; then
    rm -rf /var/www/public/*
    cp -R /app/public/. /var/www/public/
fi

# Only the web role runs migrations and warms caches; the queue and
# scheduler containers start from the same image and must not race it.
if [ "$RUKUNMUDA_ROLE" = "web" ]; then
    php artisan migrate --force

    # Rebuilt per boot rather than baked into the image: the cached
    # config would otherwise freeze this image's .env at build time.
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
