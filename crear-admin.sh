#!/bin/bash
# Script para crear el usuario admin si no existe

cd "$(dirname "$0")"

echo "▶ Verificando y creando usuario admin..."

php artisan user:create-admin

echo ""
echo "✓ Proceso completado"
echo ""
echo "Ahora puedes iniciar sesión con:"
echo "  Usuario: admin"
echo "  Contraseña: admin123"
