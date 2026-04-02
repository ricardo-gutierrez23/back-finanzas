FROM php:8.3-cli-alpine

# Instalar dependencias del sistema y extensiones de PHP
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    linux-headers

RUN docker-php-ext-install pdo pdo_pgsql zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /app
COPY . .

# Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto
EXPOSE 8000

# Script de inicio
CMD php artisan serve --host=0.0.0.0 --port=8000
