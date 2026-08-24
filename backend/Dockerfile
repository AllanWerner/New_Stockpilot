FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        icu-dev \
        postgresql-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) pdo_pgsql intl opcache \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
