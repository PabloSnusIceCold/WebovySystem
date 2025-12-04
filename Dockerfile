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

# Nastav pracovný priečinok
WORKDIR /app

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
