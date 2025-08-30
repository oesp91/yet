FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_pgsql

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^allow_url_fopen\s*=.*/allow_url_fopen = Off/' "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY flag /
COPY ./src/* /var/www/html

RUN chown -R www-data:www-data /var/www/html