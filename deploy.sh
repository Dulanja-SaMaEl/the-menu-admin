#!/bin/bash
set -e

echo "🚀 Starting Menu deployment..."

# Pull latest code from GitHub dev branch
git fetch origin dev
git reset --hard origin/dev

# Update dependencies & run migrations
composer install --no-dev --optimize-autoloader
php artisan migrate --force

# Ensure asset directories & permissions are clean
mkdir -p public/images public/storage 2>/dev/null || true
chmod -R 755 public/images 2>/dev/null || true
chmod 644 public/images/* 2>/dev/null || true
if [ -d "public/images" ] && [ ! -d "public/Images" ]; then
    ln -s images public/Images 2>/dev/null || true
fi

# Ensure storage link
if [ ! -L "public/storage" ]; then
    php artisan storage:link 2>/dev/null || true
fi

# Rebuild cached routes, views, and configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✅ Menu deployment completed successfully!"
