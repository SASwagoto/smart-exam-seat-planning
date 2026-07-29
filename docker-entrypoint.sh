#!/bin/sh

# লারাভেলের সব স্টোরেজ ও ক্যাশ ফোল্ডারের সঠিক পারমিশন নিশ্চিত করা
echo "Setting up folder permissions..."
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R laravel:laravel /var/www/html/storage /var/www/html/bootstrap/cache

# লারাভেল ক্যাশ এবং অপ্টিমাইজেশন
echo "Optimizing Laravel configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:upgrade

# ডাটাবেস অটো-মাইগ্রেশন
echo "Running database migrations..."
php artisan migrate --force

# Nginx এবং PHP-FPM একসাথে ব্যাকগ্রাউন্ডে चालू করা
echo "Starting Web Server..."
php-fpm -D
nginx -g "daemon off;"