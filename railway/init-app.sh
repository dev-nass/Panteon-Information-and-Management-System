#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/init-app.sh`

# Exit the script if any command fails
set -e

# Run migrations
php artisan migrate --force

# Clear cache
php artisan optimize:clear

# Cache the various components of the Laravel application
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache


php artisan storage:link || true
# Restart queue workers so they pick up new cached config (MAIL_MAILER etc.)
php artisan queue:restart || true

# Start Laravel's continuous scheduler in the background
php artisan schedule:work &

# Start FrankenPHP/Caddy with your Caddyfile (keep this last)
exec docker-php-entrypoint --config /Caddyfile --adapter caddyfile