#!/bin/bash
set -e

echo "🚀 Starting The Menu deployment..."

# Pull latest code from GitHub dev branch
git fetch origin dev
git reset --hard origin/dev

# Update dependencies & run migrations
composer install --no-dev --optimize-autoloader
php artisan migrate --force

# Rebuild cached routes, views, and configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✅ The Menu deployment completed successfully!"
