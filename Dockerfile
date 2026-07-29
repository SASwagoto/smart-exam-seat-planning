# PHP + Web Server ইমেজ
FROM php:8.2-fpm-alpine

# প্রয়োজনীয় সিস্টেম ডিপেন্ডেন্সি ও PHP এক্সটেনশন ইনস্টল
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    bash \
    icu-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip bcmath intl opcache

# Composer ইনস্টল
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# প্রজেক্ট ডিরেক্টরি সেটআপ
WORKDIR /var/www/html

# ব্যাকআপ এবং পারমিশনের জন্য ইউজার তৈরি
RUN addgroup -g 1000 laravel && adduser -G laravel -g "Laravel User" -s /bin/sh -D -u 1000 laravel

# পুরো প্রজেক্ট কপি করা (লোকাল বিল্ড করা public/build সহ)
COPY --chown=laravel:laravel . .

# প্রোডাকশন কম্পোজার ইনস্টল (require-dev প্যাকেজ ছাড়া)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Nginx কনফিগারেশন তৈরি
RUN echo 'server { \
    listen 80; \
    root /var/www/html/public; \
    index index.php index.html; \
    charset utf-8; \
    location / { try_files $uri $uri/ /index.php?$query_string; } \
    location = /favicon.ico { access_log off; log_not_found off; } \
    location = /robots.txt  { access_log off; log_not_found off; } \
    error_page 404 /index.php; \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
    location ~ /\.(?!well-known).* { deny all; } \
}' > /etc/nginx/http.d/default.conf

# Render-এর জন্য ওপেন পোর্ট এক্সপোজ
EXPOSE 80

# স্টার্টআপ স্ক্রিপ্ট রান করানো
RUN chmod +x /var/www/html/docker-entrypoint.sh
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]