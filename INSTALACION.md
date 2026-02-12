# Guía de Instalación - Miramira Dashboard Laravel

## Requisitos Previos

- PHP >= 8.1
- Composer
- MySQL/MariaDB >= 5.7
- Node.js >= 16 (opcional, para assets)

## Pasos de Instalación

### 1. Instalar dependencias de Composer

```bash
composer install
```

### 2. Configurar entorno

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configurar base de datos

Edita el archivo `.env` y configura tu base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=miramira
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### 4. Crear la base de datos

```bash
mysql -u root -p
CREATE DATABASE miramira CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### 5. Ejecutar migraciones

```bash
php artisan migrate
```

### 6. Poblar datos iniciales

```bash
php artisan db:seed
```

Esto creará:
- 4 roles (admin, manager, empleado, visor)
- 4 tiendas (Luz del Tajo, Maquinista, Puerto Venecia, Xanadu)
- 1 usuario administrador (usuario: `admin`, contraseña: `admin123`)
- Datos de empresa iniciales

### 7. Iniciar servidor

```bash
php artisan serve
```

### 8. Acceder a la aplicación

Abre tu navegador en: http://localhost:8000

**Credenciales por defecto:**
- Usuario: `admin`
- Contraseña: `admin123`

## Funcionalidades Implementadas

### ✅ Sistema Completo

1. **Cierre Diario Completo:**
   - Conteo de monedas y billetes (15 denominaciones)
   - Detalle de gastos del día
   - Integración con Shopify POS
   - Cálculo automático de ventas en efectivo
   - Sistema de vales (entrada/salida)
   - Conciliación automática
   - Discrepancias entre efectivo contado y Shopify

2. **Procesamiento de PDFs:**
   - Subida de nóminas en PDF
   - Extracción automática de fecha del nombre
   - Coincidencia automática por DNI, Seguridad Social o nombre
   - Visualización de PDFs en el navegador

3. **Búsqueda y Filtros Avanzados:**
   - Búsqueda por concepto, notas
   - Filtros por tienda, período, tipo, categoría, usuario
   - Filtros personalizados por fecha

4. **Gráficas en Dashboard:**
   - Gráfica de línea con Chart.js
   - Evolución de ventas vs gastos
   - Formato de moneda en español

5. **Validaciones Robustas:**
   - Form Requests para validación
   - Mensajes de error personalizados
   - Validación específica por tipo de registro

6. **Tests Unitarios:**
   - Tests de autenticación
   - Tests de creación de registros
   - Factories para datos de prueba

## Estructura del Proyecto

```
miramira-dashboard/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controladores
│   │   ├── Middleware/      # Middleware de permisos
│   │   └── Requests/        # Form Requests (validaciones)
│   └── Models/              # Modelos Eloquent
├── database/
│   ├── migrations/          # Migraciones
│   ├── seeders/             # Seeders
│   └── factories/           # Factories para tests
├── resources/
│   └── views/               # Vistas Blade
│       ├── layouts/         # Layouts
│       ├── auth/            # Login
│       ├── dashboard/       # Dashboard
│       ├── financial/       # Registros financieros
│       ├── employees/       # Empleados
│       ├── orders/          # Pedidos
│       ├── users/           # Usuarios
│       └── company/         # Empresa
├── routes/
│   └── web.php             # Rutas web
└── tests/                   # Tests
```

## Notas Importantes

- Las contraseñas se almacenan con hash bcrypt
- Los permisos se almacenan como JSON en la tabla `roles`
- El historial de cambios se guarda como JSON
- Las nóminas se almacenan como base64 en la base de datos
- El conteo de efectivo se almacena como JSON con denominaciones

## Variables de entorno para producción

Para el despliegue en producción, asegúrate de configurar estas variables en tu proveedor de hosting (Railway, Laravel Forge, etc.):

### Base de datos (requeridas)

| Variable        | Descripción                         | Ejemplo producción      |
|----------------|-------------------------------------|-------------------------|
| `DB_CONNECTION`| Conexión (usar `mysql` en prod)     | `mysql`                 |
| `DB_HOST`      | Host del servidor MySQL             | `tu-servidor.mysql...`  |
| `DB_PORT`      | Puerto MySQL                        | `3306`                  |
| `DB_DATABASE`  | Nombre de la base de datos          | `miramira_prod`         |
| `DB_USERNAME`  | Usuario MySQL                       | `miramira_user`         |
| `DB_PASSWORD`  | Contraseña MySQL                    | *(segura)*              |

### Alternativa: URL única

Muchos proveedores permiten usar `DATABASE_URL` en lugar de las variables separadas:

```
DATABASE_URL=mysql://usuario:contraseña@host:3306/nombre_bd
```

### Otras variables importantes para producción

| Variable    | Descripción                    |
|------------|--------------------------------|
| `APP_ENV`  | Debe ser `production`          |
| `APP_DEBUG`| Debe ser `false`               |
| `APP_KEY`  | Generada con `php artisan key:generate` |

## Próximos Pasos (Opcional)

Para procesamiento avanzado de PDFs, puedes instalar:

```bash
composer require smalot/pdfparser
```

Esto permitirá extraer texto completo de los PDFs para mejor coincidencia automática.
