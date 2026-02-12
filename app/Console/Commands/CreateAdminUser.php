<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin';
    protected $description = 'Crea el usuario administrador si no existe';

    public function handle()
    {
        $username = 'admin';
        $password = 'admin123';

        $user = User::where('username', $username)->first();

        if ($user) {
            $this->info("El usuario '{$username}' ya existe.");
            
            // Verificar si la contraseña es correcta
            if (Hash::check($password, $user->password)) {
                $this->info("La contraseña es correcta.");
            } else {
                $this->warn("La contraseña no coincide. Actualizando contraseña...");
                $user->password = Hash::make($password);
                $user->save();
                $this->info("Contraseña actualizada correctamente.");
            }
            return 0;
        }

        // Buscar el rol admin
        $adminRole = Role::where('key', 'admin')->first();

        if (!$adminRole) {
            $this->error("El rol 'admin' no existe. Ejecuta primero: php artisan db:seed");
            return 1;
        }

        // Crear el usuario admin
        User::create([
            'username' => $username,
            'name' => 'Administrador',
            'email' => 'admin@miramira.com',
            'password' => Hash::make($password),
            'role_id' => $adminRole->id,
            'store_id' => null,
        ]);

        $this->info("Usuario '{$username}' creado exitosamente.");
        $this->info("Usuario: {$username}");
        $this->info("Contraseña: {$password}");

        return 0;
    }
}
