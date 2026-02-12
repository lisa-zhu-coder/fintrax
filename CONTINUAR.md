# Cómo ver el proyecto Miramira Dashboard

El proyecto no pudo arrancar porque **PHP no está instalado** en tu Mac. Tienes **tres opciones**:

---

## Opción 1: Docker (recomendada si ya usas Docker)

Si tienes [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado:

```bash
cd /Users/lisazhu/miramira-dashboard
docker compose up --build
```

Abre **http://127.0.0.1:8000** en el navegador.

- **Login:** `admin`
- **Contraseña:** `admin123`

---

## Opción 2: Homebrew + PHP

1. **Instalar Homebrew** (te pedirá contraseña de administrador):

   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```

   Al terminar, sigue las instrucciones para añadir Homebrew al `PATH` (ej. `echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> ~/.zprofile`).

2. **Instalar PHP y Composer:**

   ```bash
   brew install php composer
   ```

3. **Arrancar el proyecto:**

   ```bash
   cd /Users/lisazhu/miramira-dashboard
   ./ver-proyecto.sh
   ```

   Abre **http://127.0.0.1:8000**. Login: `admin` / `admin123`.

---

## Opción 3: MAMP

1. Instala [MAMP](https://www.mamp.info/).
2. Añade el PHP de MAMP al `PATH`, por ejemplo:

   ```bash
   export PATH="/Applications/MAMP/bin/php/php8.2.0/bin:$PATH"
   ```

3. Instala [Composer](https://getcomposer.org/) y luego:

   ```bash
   cd /Users/lisazhu/miramira-dashboard
   ./ver-proyecto.sh
   ```

---

## Resumen

| Opción   | Requisito              | Comando principal        |
|----------|------------------------|---------------------------|
| Docker   | Docker Desktop         | `docker compose up --build` |
| Homebrew | Homebrew + PHP         | `./ver-proyecto.sh`      |
| MAMP     | MAMP + Composer        | `./ver-proyecto.sh`      |

Siempre que el servidor esté en marcha: **http://127.0.0.1:8000** → Login: `admin` / `admin123`.
