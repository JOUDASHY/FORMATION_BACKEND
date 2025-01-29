#!/usr/bin/env bash

echo "Running composer"
composer global require hirak/prestissimo
composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

# Créer le lien symbolique pour le stockage
echo "Creating storage symlink..."
php artisan storage:link
