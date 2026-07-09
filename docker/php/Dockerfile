# =============================================================================
# BUILDER STAGE — compile extensions, install dependencies, build assets
# =============================================================================
FROM dunglas/frankenphp:1.9.1-php8.4-alpine AS builder

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/

RUN set -ex && \
    apk update && \
    apk add --no-cache \
        git \
        postgresql-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
        imagemagick-dev \
        libheif-dev \
        linux-headers \
        build-base \
        autoconf \
        cmake \
        make

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure zip && \
    docker-php-ext-install -j$(nproc) \
        bcmath \
        pdo_pgsql \
        zip \
        gd \
        intl \
        exif \
        sockets \
        pcntl \
        opcache && \
    pecl install redis imagick && \
    docker-php-ext-enable redis imagick opcache

WORKDIR /build

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache

# =============================================================================
# RUNTIME STAGE — production image without build tools
# =============================================================================
FROM dunglas/frankenphp:1.9.1-php8.4-alpine

RUN set -ex && \
    apk update && \
    apk add --no-cache \
        libpq \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        icu-libs \
        imagemagick \
        libheif \
        libavif \
        jpegoptim \
        optipng \
        pngquant \
        gifsicle \
        supervisor \
        curl

RUN set -ex && \
    SUPERCRONIC_URL=https://github.com/aptible/supercronic/releases/download/v0.2.29/supercronic-linux-amd64 && \
    curl -fsSLO "$SUPERCRONIC_URL" && \
    chmod +x supercronic-linux-amd64 && \
    mv supercronic-linux-amd64 /usr/local/bin/supercronic

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Raise upload limits above the app's largest validated file size (photo: 10MB)
RUN { \
        echo 'upload_max_filesize = 15M'; \
        echo 'post_max_size = 16M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.save_comments=1'; \
        echo 'opcache.enable_cli=0'; \
    } > /usr/local/etc/php/conf.d/opcache-production.ini

COPY docker/php/supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www

COPY --from=builder --chown=www-data:www-data /build /var/www

RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 80 443 443/udp

ENTRYPOINT ["supervisord", "--nodaemon", "--configuration", "/etc/supervisor/conf.d/supervisord.conf"]
