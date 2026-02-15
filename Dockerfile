FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libgmp-dev \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql gmp bcmath zip gd intl

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Crea le cartelle necessarie per Laravel
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --optimize-autoloader --no-dev
RUN npm ci && npm run build

# Cache config e routes dopo composer install
RUN php artisan config:clear \
    && php artisan view:clear

EXPOSE 8080

CMD php artisan config:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}