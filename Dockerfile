FROM php:8.2-cli

# Installa dipendenze sistema e estensioni PHP
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

# Installa Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Installa Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Build
RUN composer install --optimize-autoloader --no-dev
RUN npm ci && npm run build
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

EXPOSE 8080

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}