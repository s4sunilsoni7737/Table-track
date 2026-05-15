############################
# Stage 1 – PHP dependencies
############################
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./

# Install prod deps only (no dev, no scripts that need artisan)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --no-autoloader \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-scripts

############################
# Stage 2 – Node / Vite build
############################
FROM node:20-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json* pnpm-lock.yaml* ./
RUN npm ci --legacy-peer-deps

COPY resources ./resources
COPY public ./public
COPY scripts ./scripts
COPY vite.config.js tailwind.config.js postcss.config.js ./

RUN npm run build

############################
# Stage 3 – Final image
############################
FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libssl-dev \
        libcurl4-openssl-dev \
        zip \
        unzip \
        git \
        curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        xml \
        bcmath \
        pcntl \
        exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache config
RUN a2enmod rewrite headers

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# PHP config
COPY docker/php.ini /usr/local/etc/php/conf.d/tabletrack.ini

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy in vendor (from stage 1) and built assets (from stage 2)
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Fix storage & bootstrap/cache permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions \
             storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint: inject env, run migrations, start Apache
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
