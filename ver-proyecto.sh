#!/bin/bash
# Script para ver el proyecto Miramira Dashboard (Laravel)
# Ejecuta en la raíz del proyecto: ./ver-proyecto.sh

set -e
cd "$(dirname "$0")"

echo "▶ Miramira Dashboard - Puesta en marcha"
echo ""

# 1. Comprobar PHP y Composer
if ! command -v php &> /dev/null; then
    echo "❌ PHP no encontrado. Instala PHP 8.1+ (brew install php, MAMP, etc.)."
    exit 1
fi
if ! command -v composer &> /dev/null; then
    echo "❌ Composer no encontrado. Instálalo: https://getcomposer.org"
    exit 1
fi
echo "✓ PHP $(php -r 'echo PHP_VERSION;') y Composer encontrados"
echo ""

# 2. Dependencias
if [ ! -d "vendor" ]; then
    echo "▶ Instalando dependencias (composer install)..."
    composer install --no-interaction
    echo ""
fi

# 3. .env
if [ ! -f ".env" ]; then
    echo "▶ Creando .env con SQLite..."
    ROOT="$(pwd)"
    cat > .env << ENVFILE
APP_NAME=Miramira
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=sqlite
DB_DATABASE=${ROOT}/database/database.sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
ENVFILE
    php artisan key:generate
    echo "✓ .env creado"
    echo ""
fi

# 4. SQLite
if grep -q "sqlite" .env 2>/dev/null; then
    touch database/database.sqlite 2>/dev/null || true
    echo "✓ Base de datos SQLite lista"
fi

# 5. APP_KEY
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "▶ Generando APP_KEY..."
    php artisan key:generate
fi
echo ""

# 6. Migraciones
echo "▶ Ejecutando migraciones..."
php artisan migrate --force
echo ""

# 7. Seeder
echo "▶ Cargando datos iniciales (admin / admin123)..."
php artisan db:seed --force
echo ""

# 8. Servidor
echo "=========================================="
echo "  Abre en el navegador:"
echo "  http://127.0.0.1:8000"
echo ""
echo "  Login: admin"
echo "  Contraseña: admin123"
echo "=========================================="
echo ""
php artisan serve
