FROM php:8.3-apache

# Apache nastavenie
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /app/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Aktualizácia systémových balíkov + štandardné utility
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    procps \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Nastavenie pracovného priečinka
WORKDIR /app

# Sem skopíruj celý projekt do kontajnera
COPY . .

# Inštalácia PHP závislostí
RUN composer install --no-interaction --prefer-dist

# Nastavenie práv, aby Laravel mohol zapisovať do storage
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache
