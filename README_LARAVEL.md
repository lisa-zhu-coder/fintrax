# Miramira Dashboard - Laravel

Dashboard financiero para gestión de tiendas Miramira, migrado a Laravel con Blade.

## Requisitos

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js (opcional, para assets)

## Instalación

1. **Instalar dependencias de Composer:**
```bash
composer install
```

2. **Configurar entorno:**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Configurar base de datos en `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=miramira
DB_USERNAME=root
DB_PASSWORD=
```

4. **Ejecutar migraciones y seeders:**
```bash
php artisan migrate
php artisan db:seed
```

5. **Iniciar servidor:**
```bash
php artisan serve
```

6. **Acceder a la aplicación:**
- URL: http://localhost:8000
- Usuario: `admin`
- Contraseña: `admin123`

## Estructura del Proyecto

### Modelos
- `User` - Usuarios del sistema
- `Role` - Roles y permisos
- `Store` - Tiendas
- `Employee` - Empleados
- `Order` - Pedidos
- `OrderPayment` - Pagos de pedidos
- `Company` - Datos de la empresa
- `CompanyBusiness` - Negocios de la empresa
- `FinancialEntry` - Registros financieros
- `Payroll` - Nóminas de empleados

### Controladores
- `AuthController` - Autenticación
- `DashboardController` - Dashboard principal
- `UserController` - Gestión de usuarios
- `EmployeeController` - Gestión de empleados
- `OrderController` - Gestión de pedidos
- `CompanyController` - Datos de empresa
- `FinancialController` - Registros financieros

### Vistas Blade
- `layouts/app.blade.php` - Layout principal
- `auth/login.blade.php` - Login
- `dashboard/index.blade.php` - Dashboard
- `users/*` - Gestión de usuarios
- `employees/*` - Gestión de empleados
- `orders/*` - Gestión de pedidos
- `company/*` - Datos de empresa
- `financial/*` - Registros financieros

## Permisos

El sistema utiliza un sistema de roles y permisos:

- **admin**: Acceso completo
- **manager**: Puede crear y editar registros diarios
- **empleado**: Solo puede crear registros diarios
- **visor**: Solo puede visualizar

## Funcionalidades

- ✅ Autenticación de usuarios
- ✅ Sistema de roles y permisos
- ✅ Gestión de tiendas
- ✅ Gestión de empleados
- ✅ Gestión de pedidos
- ✅ Registros financieros (ingresos, gastos, cierres diarios)
- ✅ Datos de empresa
- ✅ Exportación a CSV
- ✅ Filtros por tienda y período

## Migración desde localStorage

Para migrar datos desde la versión anterior (localStorage), puedes crear un script de migración que lea los datos del localStorage y los inserte en la base de datos.

## Notas

- Las contraseñas se almacenan con hash usando bcrypt
- Los permisos se almacenan como JSON en la tabla `roles`
- El historial de cambios en pedidos se guarda como JSON
