FROM php:8.4-fpm-alpine

# Dépendances système pour les extensions et l'outillage projet.
RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        icu-libs \
        icu-data-full \
        libzip-dev \
        oniguruma-dev \
        zip \
        unzip \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && apk del --no-network $PHPIZE_DEPS

# Composer + Symfony CLI (utile pour les diagnostics locaux).
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
RUN wget -qO- https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# OPcache tuning dev — préfère réactivité au cache long terme.
COPY docker/php/opcache.ini  /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/php-dev.ini  /usr/local/etc/php/conf.d/zz-php-dev.ini

WORKDIR /var/www/html
