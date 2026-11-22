#!/bin/bash
set -e

echo "🚀 SupportBoard Docker Startup Script"
echo "======================================"
echo ""

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
until mysqladmin ping -h db -u root -proot_password --silent; do
    echo "   MySQL is still starting up..."
    sleep 2
done
echo "✅ MySQL is ready!"
echo ""

# Initialize Laravel application
echo "📦 Initializing Laravel application..."

# Check if app is already initialized
if [ -f "/var/www/html/.env" ] && [ -d "/var/www/html/vendor" ]; then
    echo "✅ Application already initialized. Skipping setup."
else
    echo "🔑 Generating application key..."
    docker-compose exec -T app php artisan key:generate --force

    echo "🔐 Generating JWT secret..."
    docker-compose exec -T app php artisan jwt:secret --force

    echo "🗃️  Running database migrations..."
    docker-compose exec -T app php artisan migrate --force

    echo "🌱 Seeding database..."
    docker-compose exec -T app php artisan db:seed --class=DatabaseSeeder --force

    echo "🔗 Creating storage link..."
    docker-compose exec -T app php artisan storage:link

    echo "⚡ Caching configuration..."
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
fi

echo ""
echo "======================================"
echo "✅ SupportBoard is ready!"
echo ""
echo "📝 Access your application at:"
echo "   http://localhost:8000"
echo ""
echo "🔑 Demo credentials:"
echo "   Admin: admin@supportboard.local / admin123"
echo "   Agent: agent@supportboard.local / agent123"
echo "   User:  user@example.com / user123"
echo ""
echo "📱 WhatsApp Setup:"
echo "   Visit /installer to configure WhatsApp Business API"
echo ""
echo "🛑 To stop: docker-compose down"
echo "📋 Logs: docker-compose logs -f"
echo "======================================"
