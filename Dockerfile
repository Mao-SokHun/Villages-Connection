FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    curl \
    unzip \
    git \
    ffmpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads

EXPOSE 9000

CMD ["php-fpm"]