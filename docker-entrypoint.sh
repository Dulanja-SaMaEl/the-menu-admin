#!/bin/sh
set -e

echo '==> Running database migrations...'
php artisan migrate --force --no-interaction || true

echo '==> Optimizing caches...'
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo '==> Starting web server...'
exec "$@"
