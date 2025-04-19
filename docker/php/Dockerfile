FROM php:8.3-fpm

# 1) Sistema y extensiones PHP (incluyendo pcntl para Horizon)
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libonig-dev libpng-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql zip bcmath mbstring pcntl

# 2) Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 3) Copiamos sólo composer.json y composer.lock
COPY composer.json composer.lock ./

# 4) Instalamos dependencias y Horizon *sin* ejecutar scripts de post‑install
RUN composer require laravel/horizon --no-scripts \
    && composer install --no-dev --no-scripts --optimize-autoloader

# 5) Ahora sí copiamos TODO tu código, incluido artisan
COPY . .

# 6) Generamos el autoloader definitivo y ejecutamos los scripts
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

# 7) Preparamos el entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
