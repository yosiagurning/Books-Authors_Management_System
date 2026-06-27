FROM php:8.2-fpm

WORKDIR /var/www

# dependency system
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# copy project
COPY . .

RUN composer install --no-dev --optimize-autoloader

CMD ["php-fpm"]