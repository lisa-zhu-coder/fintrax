#!/bin/sh
set -e
cd /var/www

# Directorios Laravel
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache

# Dependencias (si se monta código y vendor no existe)
if [ ! -d vendor ]; then
  composer install --no-dev --optimize-autoloader
fi

# .env
if [ ! -f .env ]; then
  cp .env.example .env
fi
if ! grep -q 'APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

# SQLite
touch database/database.sqlite

# Migraciones
php artisan migrate --force

# Seed solo en primera ejecución
if [ ! -f .docker-seeded ]; then
  php artisan db:seed --force
  touch .docker-seeded
  echo ">>> Datos iniciales cargados (admin / admin123)"
fi

exec php artisan serve --host=0.0.0.0 --port=8000
