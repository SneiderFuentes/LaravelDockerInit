#!/bin/sh
set -e

until mysqladmin ping -h"$DB_HOST" --silent; do
  >&2 echo "[Entrypoint] Esperando a MySQL…"
  sleep 2
done

# 0) Asegurar permisos correctos en directorios de almacenamiento
echo "=> Configurando permisos..."
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# 1) Generar APP_KEY si no existe
if [ -f /var/www/.env ] && ! grep -q '^APP_KEY=' /var/www/.env; then
  echo "=> Generando APP_KEY..."
  php artisan key:generate --ansi --force
fi

# 2) Migraciones
echo "=> Ejecutando migraciones principales..."
php artisan migrate --force

# echo "=> Ejecutando migraciones de centros..."
# php artisan migrate --path=database/migrations/centers --realpath --force

# 4) Cachear configuraciones, rutas y vistas
php artisan config:clear --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi

# 5) Arrancar el comando que se pase (php-fpm o php artisan horizon)
exec "$@"
