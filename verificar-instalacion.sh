#!/bin/bash
# Script para verificar si PHP y Composer están listos
# Ejecuta: ./verificar-instalacion.sh

echo "🔍 Verificando instalación..."
echo ""

# Configurar Homebrew
eval "$(/usr/local/bin/brew shellenv bash)" 2>/dev/null || eval "$(/opt/homebrew/bin/brew shellenv)" 2>/dev/null

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r 'echo PHP_VERSION;' 2>/dev/null)
    echo "✅ PHP instalado: $PHP_VERSION"
    
    # Verificar versión mínima (8.1)
    PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
    PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)
    if [ "$PHP_MAJOR" -ge 8 ] && [ "$PHP_MINOR" -ge 1 ]; then
        echo "   ✓ Versión compatible (8.1+)"
    else
        echo "   ⚠️  Versión muy antigua, puede haber problemas"
    fi
else
    echo "❌ PHP no instalado"
    echo "   Ejecuta: brew install php"
fi
echo ""

# Verificar Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version 2>/dev/null | head -1)
    echo "✅ Composer instalado: $COMPOSER_VERSION"
else
    echo "❌ Composer no instalado"
    echo "   Ejecuta: brew install composer"
fi
echo ""

# Resumen
if command -v php &> /dev/null && command -v composer &> /dev/null; then
    echo "=========================================="
    echo "✅ ¡TODO LISTO!"
    echo ""
    echo "Ahora puedes ejecutar:"
    echo "  cd /Users/lisazhu/miramira-dashboard"
    echo "  ./ver-proyecto.sh"
    echo "=========================================="
else
    echo "=========================================="
    echo "⏳ Aún faltan componentes"
    echo ""
    echo "Verifica el progreso de Homebrew:"
    echo "  ps aux | grep brew | grep -v grep"
    echo ""
    echo "O espera a que termine la instalación"
    echo "=========================================="
fi
