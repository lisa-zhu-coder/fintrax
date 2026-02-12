# Crear superusuario (producción o local)

El seeder `CreateSuperUserSeeder` crea o actualiza el usuario:

- **Usuario:** lisa.zhu  
- **Nombre:** Lisa Zhu  
- **Contraseña:** 060698  
- **Rol:** Super Administrador (todos los permisos)

## Ejecutar contra la base de datos de producción

### Opción 1: Script con .env.production (recomendado)

1. Crea un archivo `.env.production` en la raíz del proyecto (está en `.gitignore`, no se sube al repo) con:

   ```
   DB_CONNECTION=mysql
   DB_HOST=db-a110bd27-e7e1-4f22-acb6-651a5079332d.eu-central-1.public.db.laravel.cloud
   DB_PORT=3306
   DB_DATABASE=tu_base_datos_pro
   DB_USERNAME=xaut0olw9djbqaa1
   DB_PASSWORD=8IawXOHJZ3aMdAyqSVvJ
   ```

   Sustituye `DB_DATABASE` por el nombre real de la base de datos de producción si lo conoces.

2. Ejecuta el script:

   ```bash
   chmod +x run-superuser-on-pro.sh
   ./run-superuser-on-pro.sh
   ```

   El script carga las variables de `.env.production` y ejecuta el seeder. Tu `.env` actual no se modifica.

### Opción 2: Variables de entorno en una línea

Sin crear archivos, pasando las variables al comando (sustituye `NOMBRE_BD` por el nombre de la base de datos de PRO):

```bash
DB_CONNECTION=mysql \
DB_HOST=db-a110bd27-e7e1-4f22-acb6-651a5079332d.eu-central-1.public.db.laravel.cloud \
DB_PORT=3306 \
DB_DATABASE=NOMBRE_BD \
DB_USERNAME=xaut0olw9djbqaa1 \
DB_PASSWORD='8IawXOHJZ3aMdAyqSVvJ' \
php artisan db:seed --class=CreateSuperUserSeeder
```

### Opción 3: Cambiar .env temporalmente

1. Guarda una copia: `cp .env .env.backup`
2. Edita `.env` y pon las credenciales de producción en `DB_*`
3. Ejecuta: `php artisan db:seed --class=CreateSuperUserSeeder`
4. Restaura: `mv .env.backup .env`

## Ejecutar en local (tu .env actual)

```bash
php artisan db:seed --class=CreateSuperUserSeeder
```

Si el rol `super_admin` no existe, el seeder ejecuta antes `RoleSeeder` para crearlo.
