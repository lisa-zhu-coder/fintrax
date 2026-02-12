#!/usr/bin/env bash
# Carga las variables de .env.production y ejecuta el seeder del superusuario.
# Crea .env.production a partir de .env.production.example con las credenciales de PRO.

set -e
if [ ! -f .env.production ]; then
  echo "No existe .env.production. Copia .env.production.example a .env.production y rellena DB_*."
  exit 1
fi

set -a
source .env.production
set +a

php artisan db:seed --class=CreateSuperUserSeeder
