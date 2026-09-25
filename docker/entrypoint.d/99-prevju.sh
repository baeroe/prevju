#!/bin/sh
set -e
cd /var/www/html

# APP_KEY optional: generate once and persist in the data volume
if [ -z "$APP_KEY" ]; then
    KEY_FILE=storage/app/.app-key
    [ -s "$KEY_FILE" ] || php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$KEY_FILE"
    APP_KEY=$(cat "$KEY_FILE")
    export APP_KEY
fi

touch "$DB_DATABASE"
php artisan migrate --force
php artisan app:ensure-admin
php artisan storage:unlink >/dev/null 2>&1 || true
php artisan optimize
