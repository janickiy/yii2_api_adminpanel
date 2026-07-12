FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git unzip icu-dev libzip-dev libpng-dev libxml2-dev oniguruma-dev \
    libpq-dev libmemcached-dev zlib-dev linux-headers $PHPIZE_DEPS

RUN docker-php-ext-install pdo_mysql pdo_pgsql intl zip bcmath gd mbstring

RUN pecl install redis memcached xdebug \
    && docker-php-ext-enable redis memcached xdebug

RUN EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')" \
    && php -r 'copy("https://getcomposer.org/installer", "composer-setup.php");' \
    && ACTUAL_CHECKSUM="$(php -r 'echo hash_file("sha384", "composer-setup.php");')" \
    && test "$EXPECTED_CHECKSUM" = "$ACTUAL_CHECKSUM" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet \
    && rm composer-setup.php

WORKDIR /var/www/html

CMD ["php-fpm"]
