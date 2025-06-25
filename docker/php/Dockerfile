FROM php:8.3-fpm

# 1) Sistema y extensiones PHP (incluyendo pcntl para Horizon)
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libonig-dev libpng-dev \
    default-mysql-client \
    ghostscript \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql zip bcmath mbstring pcntl

# 2) Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 3) Copiamos sólo composer.json y composer.lock
COPY composer.json composer.lock ./

# 4) Instalamos dependencias y Horizon *sin* ejecutar scripts de post‑install
RUN composer install --no-dev --no-scripts --optimize-autoloader

# 5) Ahora sí copiamos TODO tu código, incluido artisan
COPY . .

# 6) Generamos el autoloader definitivo y ejecutamos los scripts
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

# Publica config de Horizon una sola vez
RUN php artisan horizon:install --ansi

# Crear directorios de storage si no existen y establecer permisos
RUN mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage \
    && chown -R www-data:www-data /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache \
    && chmod -R g+s /var/www/storage \
    && echo "umask 0002" >> /etc/profile \
    && echo "umask 0002" >> /etc/bash.bashrc

# Configurar usuario y grupo para PHP-FPM
RUN sed -i 's/user = www-data/user = www-data/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = www-data/g' /usr/local/etc/php-fpm.d/www.conf

# Hacer que FPM escuche en todas las interfaces
RUN sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

# 7) Preparamos el entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENV TZ=America/Bogota
RUN echo "date.timezone=America/Bogota" > /usr/local/etc/php/conf.d/timezone.ini

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
