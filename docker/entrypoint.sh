#!/usr/bin/env sh
set -e

php artisan config:clear || true
php artisan cache:clear || true

php artisan migrate --force

exec "$@"
