#!/bin/sh


php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:upgrade


php artisan migrate --force


php-fpm -D
nginx -g "daemon off;"