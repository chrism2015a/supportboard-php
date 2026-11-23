FROM php:8.2-fpm

# Set Composer environment variables to bypass security advisory blocking
ENV COMPOSER_AUDIT=0
ENV COMPOSER_AUDIT_BLOCK=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install ALL required system dependencies (including libxml for soap, libpng for gd)
RUN apt-get update && apt-get install -y \
    libxml2-dev \
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
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        soap \
        xml \
        opcache \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create all runtime directories (these are gitignored runtime dirs)
RUN mkdir -p storage/framework/{cache,sessions,views,testing} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Install PHP dependencies (optimized for production)
# --no-security-blocking bypasses security advisory blocking for Laravel 10
# Retry up to 3 times with 5 second delays to handle GitHub API timeouts
RUN for i in 1 2 3; do \
    composer install --optimize-autoloader --no-dev --no-interaction --no-progress --no-security-blocking && break || \
    (echo "Attempt $i failed, retrying in 5 seconds..." && sleep 5); \
done

# Mount custom pool config (overridden at runtime via volume)
# This makes FPM run as root to fix volume mount permission issues
EXPOSE 9000

# Start PHP-FPM with custom pool override
CMD ["php-fpm", "--allow-to-run-as-root"]
