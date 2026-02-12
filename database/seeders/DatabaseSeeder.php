<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyBusiness;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('key', 'admin')->first();
        if (!$adminRole) {
            $adminRole = Role::first();
        }

        // Tiendas
        $luzDelTajo = Store::create([
            'name' => 'Miramira - Luz del Tajo',
            'slug' => 'luz_del_tajo',
        ]);

        $maquinista = Store::create([
            'name' => 'Miramira - Maquinista',
            'slug' => 'maquinista',
        ]);

        $puertoVenecia = Store::create([
            'name' => 'Miramira - Puerto Venecia',
            'slug' => 'puerto_venecia',
        ]);

        $xanadu = Store::create([
            'name' => 'Miramira - Xanadu',
            'slug' => 'xanadu',
        ]);

        // Usuario super_admin (sin empresa fija, puede acceder a todas)
        $superAdminRole = Role::where('key', 'super_admin')->first();
        if ($superAdminRole) {
            User::create([
                'username' => 'superadmin',
                'name' => 'Super Administrador',
                'email' => 'superadmin@fintrax.com',
                'password' => Hash::make('super123'),
                'role_id' => $superAdminRole->id,
                'store_id' => null,
                'company_id' => null, // Super admin no pertenece a ninguna empresa
            ]);
        }

        // Usuario admin (pertenece a la empresa principal)
        User::create([
            'username' => 'admin',
            'name' => 'Administrador',
            'email' => 'admin@miramira.com',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
            'store_id' => null,
            // company_id se asignará en la migración de datos
        ]);

        // Empresa
        Company::create([
            'name' => 'Miramira',
            'cif' => '',
            'fiscal_street' => '',
            'fiscal_postal_code' => '',
            'fiscal_city' => '',
            'fiscal_email' => '',
        ]);

        // Negocios
        CompanyBusiness::create([
            'name' => 'Miramira - Luz del Tajo',
            'slug' => 'luz_del_tajo',
            'street' => '',
            'postal_code' => '',
            'city' => '',
            'email' => '',
        ]);

        CompanyBusiness::create([
            'name' => 'Miramira - Maquinista',
            'slug' => 'maquinista',
            'street' => '',
            'postal_code' => '',
            'city' => '',
            'email' => '',
        ]);

        CompanyBusiness::create([
            'name' => 'Miramira - Puerto Venecia',
            'slug' => 'puerto_venecia',
            'street' => '',
            'postal_code' => '',
            'city' => '',
            'email' => '',
        ]);

        CompanyBusiness::create([
            'name' => 'Miramira - Xanadu',
            'slug' => 'xanadu',
            'street' => '',
            'postal_code' => '',
            'city' => '',
            'email' => '',
        ]);
    }
}
