#!/bin/sh
# Docker entrypoint script that ensures Laravel runtime directories exist
# before starting PHP-FPM.

# Ensure all Laravel runtime directories exist (volume mount may overwrite them)
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions for root user (FPM runs as root)
chown -R root:root /var/www/html/storage
chown -R root:root /var/www/html/bootstrap
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap

# If artisan is available, clear all caches (non-critical, continue on error)
if [ -f /var/www/html/artisan ]; then
    cd /var/www/html
    echo "Clearing Laravel caches..."
    php artisan config:clear 2>/dev/null || echo "Warning: config:clear failed"
    php artisan cache:clear 2>/dev/null || echo "Warning: cache:clear failed"
    php artisan route:clear 2>/dev/null || echo "Warning: route:clear failed"
    php artisan view:clear 2>/dev/null || echo "Warning: view:clear failed"
    php artisan optimize:clear 2>/dev/null || echo "Warning: optimize:clear failed"
    echo "Laravel caches cleared."
fi

echo "Laravel runtime directories ready. Starting PHP-FPM..."

# Execute the passed command (php-fpm)
exec "$@"
