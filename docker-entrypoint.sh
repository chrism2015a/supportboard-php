#!/bin/sh
# Docker entrypoint script that ensures Laravel runtime directories exist
# before starting PHP-FPM.

echo "Setting up Laravel runtime directories..."

# Ensure all Laravel runtime directories exist
# These directories are gitignored so they won't be in the repo
# When docker-compose mounts ./storage from the host, these subdirs
# need to be created at container startup
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

echo "Setting permissions..."

# Set proper permissions for root user (FPM runs as root)
chown -R root:root /var/www/html/storage
chown -R root:root /var/www/html/bootstrap
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap

echo "Laravel runtime directories ready."
echo "Starting PHP-FPM..."

# Execute the passed command (php-fpm)
exec "$@"
