FROM serversideup/php:8.4-fpm-nginx

ENV AUTORUN_ENABLED=false \
    SSL_MODE=off \
    PHP_OPCACHE_ENABLE=1 \
    PHP_UPLOAD_MAX_FILE_SIZE=200M \
    PHP_POST_MAX_SIZE=200M \
    NGINX_CLIENT_MAX_BODY_SIZE=200M \
    APP_NAME=prevju \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/storage/app/database.sqlite \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

USER root
RUN install-php-extensions intl
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
COPY --chown=www-data:www-data . /var/www/html
USER www-data

RUN composer install --no-dev --no-interaction --optimize-autoloader \
    && php artisan filament:assets
