# Guía Paso a Paso: Instalar PHP y Ver el Proyecto

## 📋 PASO 1: Instalar Homebrew

1. Abre la **Terminal** (Aplicaciones > Utilidades > Terminal, o busca "Terminal" con Cmd+Espacio)

2. Copia y pega este comando completo y presiona Enter:

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

3. Te pedirá tu **contraseña de administrador**:
   - Escribe tu contraseña (no verás nada mientras escribes, es normal)
   - Presiona Enter
   - Espera a que termine (puede tardar 5-10 minutos)

4. Al finalizar, verás un mensaje como:
   ```
   Next steps:
   - Run these commands in your terminal to add Homebrew to your PATH:
   ```

---

## 📋 PASO 2: Configurar Homebrew en el PATH

Copia y pega estos dos comandos (uno por uno):

```bash
echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> ~/.zprofile
```

Presiona Enter, luego:

```bash
eval "$(/opt/homebrew/bin/brew shellenv)"
```

Presiona Enter.

---

## 📋 PASO 3: Verificar que Homebrew funciona

Ejecuta:

```bash
brew --version
```

Deberías ver algo como: `Homebrew 4.x.x`

Si ves un error, avísame.

---

## 📋 PASO 4: Instalar PHP y Composer

Ejecuta:

```bash
brew install php composer
```

Esto puede tardar 10-15 minutos. Espera a que termine.

---

## 📋 PASO 5: Verificar que PHP está instalado

Ejecuta:

```bash
php -v
```

Deberías ver algo como: `PHP 8.2.x` o `PHP 8.3.x`

---

## 📋 PASO 6: Ir a la carpeta del proyecto

```bash
cd /Users/lisazhu/miramira-dashboard
```

---

## 📋 PASO 7: Arrancar el proyecto

```bash
./ver-proyecto.sh
```

Este script:
- Instalará las dependencias de Composer
- Creará la base de datos SQLite
- Ejecutará las migraciones
- Cargará datos iniciales
- Arrancará el servidor

---

## 📋 PASO 8: Abrir en el navegador

Cuando veas este mensaje:
```
Laravel development server started: http://127.0.0.1:8000
```

1. Abre **Safari** o **Chrome**
2. En la barra de direcciones escribe: `http://127.0.0.1:8000`
3. Presiona Enter

---

## 🔐 Credenciales de Login

- **Usuario:** `admin`
- **Contraseña:** `admin123`

---

## ❓ Si algo falla

- Si ves errores, cópialos y compártelos
- Si el servidor no arranca, verifica que el puerto 8000 no esté ocupado
- Si necesitas ayuda en cualquier paso, avísame
