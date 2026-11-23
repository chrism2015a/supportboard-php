#!/bin/sh
# Docker entrypoint script that ensures Laravel runtime directories exist
# and clears cached config/routes before starting PHP-FPM.

set -e

# Ensure all Laravel runtime directories exist (volume mount may overwrite them)
mkdir -p /var/www/html/storage/framework/{cache,sessions,views,testing}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions for www-data/root user
chown -R root:root /var/www/html/storage
chown -R root:root /var/www/html/bootstrap

# If artisan is available, clear all caches to ensure fresh routes/config
if [ -f /var/www/html/artisan ]; then
    cd /var/www/html
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    php artisan optimize:clear 2>/dev/null || true
fi

# Execute the passed command (php-fpm)
exec "$@"
