#!/bin/sh

# ফোল্ডার পারমিশন ফিক্স
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# ক্যাশ ও অপ্টিমাইজেশন (ফেল করলেও যেন সার্ভার চালু থাকে তাই || true ব্যবহার করা)
php artisan config:clear || true
php artisan cache:clear || true

# ডাটাবেস অটো-মাইগ্রেশন
php artisan migrate --force || true

# সার্ভার স্টার্ট
php-fpm -D
nginx -g "daemon off;"