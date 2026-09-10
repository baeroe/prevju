#!/bin/sh
set -e
cd /var/www/html
touch "$DB_DATABASE"
php artisan migrate --force
php artisan app:ensure-admin
php artisan storage:unlink >/dev/null 2>&1 || true
php artisan optimize
