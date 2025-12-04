FROM php:8.3-fpm-alpine

# Inštalácia systémových balíkov
RUN apk add --no-cache \
    bash \
    curl \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# Inštalácia PHP rozšírení pre Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    mbstring

# Nastav pracovný priečinok
WORKDIR /app

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
