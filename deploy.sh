#!/bin/bash
# deploy.sh - Run migrations on Render

php artisan migrate --force
php artisan db:seed --force
php artisan config:clear
php artisan cache:clear