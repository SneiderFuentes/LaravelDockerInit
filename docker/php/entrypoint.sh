#!/bin/sh
set -e

until mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl -e "SELECT 1;" > /dev/null 2>&1; do
  >&2 echo "[Entrypoint] Esperando a MySQL…"
  sleep 2
done

# 0) Asegurar permisos correctos en directorios de almacenamiento
echo "=> Configurando permisos..."
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# 0-A) Garantizar que /var/www/vendor exista con permisos válidos
mkdir -p /var/www/vendor
chown -R www-data:www-data /var/www/vendor
chmod -R 775 /var/www/vendor

# 0-B) Configurar caché y tmp en /tmp (evita problemas con bind-mount)
export COMPOSER_HOME=/tmp/composer
export COMPOSER_CACHE_DIR=/tmp/composer-cache

# 0) Instalar vendor si falta
if [ ! -f /var/www/vendor/autoload.php ]; then
  echo "=> Instalando dependencias con Composer..."
  composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ansi
fi

# Configuración de base de datos (solo una vez, con bloqueo)
LOCK_FILE="/var/www/storage/app/.setup_lock"
SETUP_COMPLETE="/var/www/storage/app/.setup_complete"

if [ ! -f "$SETUP_COMPLETE" ]; then
  # Intentar obtener el bloqueo (solo un contenedor lo conseguirá)
  if (set -C; echo $$ > "$LOCK_FILE") 2>/dev/null; then
    echo "=> Este contenedor ejecutará la configuración inicial..."

    # Generar APP_KEY si no existe
    if [ -f /var/www/.env ] && ! grep -q '^APP_KEY=' /var/www/.env; then
      echo "=> Generando APP_KEY..."
      php artisan key:generate --ansi --force
    fi

    # Migraciones
    echo "=> Ejecutando migraciones..."
    php artisan migrate --force

    # Seeders
    echo "=> Ejecutando seeders..."
    php artisan db:seed --force

    # Cachear configuraciones
    echo "=> Cacheando configuraciones..."
    php artisan config:clear --ansi
    php artisan route:clear --ansi
    php artisan view:clear --ansi
    php artisan cache:clear --ansi
    php artisan config:cache
    php artisan route:cache --ansi
    php artisan view:cache --ansi

    # Marcar como completado y liberar el bloqueo
    touch "$SETUP_COMPLETE"
    rm -f "$LOCK_FILE"

    echo "=> Configuración inicial completada."
  else
    # Este contenedor no obtuvo el bloqueo, esperar a que termine el otro
    echo "=> Otro contenedor está ejecutando la configuración, esperando..."
    while [ -f "$LOCK_FILE" ] || [ ! -f "$SETUP_COMPLETE" ]; do
      sleep 1
    done
    echo "=> Configuración completada por otro contenedor, continuando..."
  fi
fi

# 5) Arrancar el comando que se pase (php-fpm o php artisan horizon)
exec "$@"
