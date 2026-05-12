# ============================================================
# Stage: PHP-FPM Application
# ============================================================
FROM php:8.4-fpm-alpine


# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy custom PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better Docker layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (no scripts yet - project files not copied)
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copy rest of the application
COPY . .

# Ensure required directories exist with proper permissions before dump-autoload
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions \
    storage/framework/views storage/logs \
    && chmod -R 777 bootstrap/cache storage

# Generate optimized autoloader (skip scripts - artisan runs inside container at runtime)
RUN composer dump-autoload --optimize --no-scripts


# Set storage & cache permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
