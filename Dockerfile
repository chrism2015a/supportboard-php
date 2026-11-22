FROM php:8.2-fpm

# Install all required system dependencies in one layer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath soap xml opcache \
    && rm -rf /var/lib/apt/lists/*

# Install and enable redis for caching/queues (optional but recommended)
# RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions before installing dependencies
RUN chown -R www-data:www-data storage bootstrap/cache

# Install PHP dependencies (optimized for production)
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-progress

# Set file permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
