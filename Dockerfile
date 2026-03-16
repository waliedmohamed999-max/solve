FROM php:8.2-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y git unzip libzip-dev sqlite3 libsqlite3-dev \
    && docker-php-ext-install zip pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

RUN rm -f bootstrap/cache/config.php
RUN rm -f bootstrap/cache/routes-v7.php
RUN rm -f bootstrap/cache/packages.php
RUN rm -f bootstrap/cache/services.php

RUN php artisan config:clear || true
RUN php artisan cache:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true
RUN php artisan optimize:clear || true

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000
