FROM composer:2 AS vendor

WORKDIR /var/www
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist --optimize-autoloader

FROM php:8.3-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/vendor ./vendor
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-auction-marketplace.ini
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/auction-entrypoint

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["auction-entrypoint"]
CMD ["php-fpm"]
