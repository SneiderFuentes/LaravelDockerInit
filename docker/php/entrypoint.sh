#!/bin/sh
set -e

# 1) Generar APP_KEY si no existe
if [ -f /var/www/.env ] && ! grep -q '^APP_KEY=' /var/www/.env; then
  echo "=> Generando APP_KEY..."
  php artisan key:generate --ansi --force
fi

# 2) Migraciones
echo "=> Ejecutando migraciones..."
php artisan migrate --force

# 3) Publicar y cachear configuración de Horizon
echo "=> Publicando configuracion de Horizon..."
php artisan horizon:install --ansi
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi

# 4) Arrancar el comando que se pase (php-fpm o php artisan horizon)
exec "$@"
