FROM php:8.3-fpm-alpine

# Inštalácia systémových balíkov
RUN apk add --no-cache \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    curl \
    bash

# Inštalácia PHP rozšírení pre Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    mbstring

RUN docker-php-ext-install pdo pdo_mysql zip intl

# --- Dev on Windows: avoid bind-mount permission issues for storage/framework/views ---
# php-fpm forbids running workers as root. Use 'nobody' and make Laravel writable dirs permissive.
RUN sed -i 's/^user = .*/user = nobody/' /usr/local/etc/php-fpm.d/www.conf \
 && sed -i 's/^group = .*/group = nobody/' /usr/local/etc/php-fpm.d/www.conf

# Nastav pracovný priečinok
WORKDIR /app

# Ensure Laravel runtime dirs exist and are writable even on bind mounts (dev only).
RUN mkdir -p /app/storage/framework/views /app/storage/framework/cache /app/storage/framework/sessions /app/bootstrap/cache \
 && chmod -R 777 /app/storage /app/bootstrap/cache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
