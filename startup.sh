#!/bin/bash
set -e

cd /var/www/html

echo "📦 Running database migrations..."
php artisan migrate --force

echo "🚀 Starting Apache..."
exec apache2-foreground
