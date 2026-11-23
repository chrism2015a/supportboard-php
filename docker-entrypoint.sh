#!/bin/sh
# Docker entrypoint script that ensures Laravel runtime directories exist
# before starting PHP-FPM. This handles the case where the storage volume
# mount from the host overwrites the framework directories.

# Ensure all Laravel storage/framework directories exist
mkdir -p /var/www/html/storage/framework/{cache,sessions,views,testing}

# Ensure bootstrap/cache exists
mkdir -p /var/www/html/bootstrap/cache

# Ensure storage/logs exists
mkdir -p /var/www/html/storage/logs

# Set proper permissions for www-data/root user
chown -R root:root /var/www/html/storage
chown -R root:root /var/www/html/bootstrap

# Execute the passed command (php-fpm)
exec "$@"
