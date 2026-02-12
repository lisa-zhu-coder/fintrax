#!/bin/bash
# Ver el proyecto con Docker. Requiere Docker Desktop.
set -e
cd "$(dirname "$0")"
if ! command -v docker &>/dev/null; then
  echo "❌ Docker no encontrado. Instala Docker Desktop: https://www.docker.com/products/docker-desktop/"
  exit 1
fi
echo "▶ Miramira Dashboard (Docker)"
echo "  Abre http://127.0.0.1:8000 — Login: admin / admin123"
echo ""
docker compose up --build
