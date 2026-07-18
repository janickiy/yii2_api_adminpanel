FROM composer:2 AS composer

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        icu-libs libzip libpq libpng libjpeg-turbo freetype libmemcached-libs zstd-libs \
        postgresql-client \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS icu-dev libzip-dev libpq-dev libpng-dev libjpeg-turbo-dev \
        freetype-dev libmemcached-dev zstd-dev cyrus-sasl-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(getconf _NPROCESSORS_ONLN)" \
        bcmath gd intl opcache pcntl pdo_pgsql zip \
    && pecl install redis memcached \
    && docker-php-ext-enable redis memcached \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm", "-F"]
