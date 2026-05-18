FROM php:8.0-fpm

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN apt-get update && apt-get install -y libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

RUN echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
EXPOSE 9000
