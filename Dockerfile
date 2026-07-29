# Stage 2: PHP and Web server
FROM php:8.2-fpm-alpine

# Required Depedency
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

# Composer Install
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Project Direcotry
WORKDIR /var/www/html

# Backup and Permission for cloude
RUN addgroup -g 1000 laravel && adduser -G laravel -g "Laravel User" -s /bin/sh -D -u 1000 laravel

# Build Code and Copy
COPY --chown=laravel:laravel . .
COPY --from=node-builder --chown=laravel:laravel /app/public/build ./public/build

# Production Composer Install
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Nginx Configuration
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

# Render- Open Port Exposed
EXPOSE 80

# Startup script
RUN chmod +x /var/www/html/docker-entrypoint.sh
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]