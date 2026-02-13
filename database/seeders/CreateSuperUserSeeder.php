<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
/**
 * Crea un superusuario (rol super_admin) con todos los permisos.
 *
 * Para ejecutar contra la base de datos de PRODUCCIÓN:
 * 1. Configura temporalmente tu .env con las credenciales de producción (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
 * 2. Ejecuta: php artisan db:seed --class=CreateSuperUserSeeder
 * 3. Restaura tu .env con los valores de desarrollo si los habías cambiado.
 *
 * O con variables de entorno en una sola línea (sin tocar .env):
 * DB_CONNECTION=mysql DB_HOST=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=... php artisan db:seed --class=CreateSuperUserSeeder
 */
class CreateSuperUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('key', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->command->warn('El rol super_admin no existe. Ejecutando RoleSeeder...');
            $this->call(RoleSeeder::class);
            $superAdminRole = Role::where('key', 'super_admin')->first();
        }

        if (!$superAdminRole) {
            $this->command->error('No se pudo obtener el rol super_admin.');
            return;
        }

        $username = 'lisazhu';
        $password = '060698';
        $user = User::where('username', $username)->first();

        if ($user) {
            $user->update([
                'name' => 'Lisa Zhu',
                'password' => $password,
                'role_id' => $superAdminRole->id,
                'company_id' => null,
                'store_id' => null,
            ]);
            $this->command->info("Superusuario actualizado: {$username}");
        } else {
            User::create([
                'username' => $username,
                'name' => 'Lisa Zhu',
                'email' => null,
                'password' => $password,
                'role_id' => $superAdminRole->id,
                'company_id' => null,
                'store_id' => null,
            ]);
            $this->command->info("Superusuario creado: {$username} (contraseña: {$password})");
        }
    }
}
