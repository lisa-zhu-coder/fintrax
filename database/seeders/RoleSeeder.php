<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'key' => 'super_admin',
                'name' => 'Super Administrador',
                'description' => 'Acceso total a todas las empresas. Puede crear empresas, ver todas las empresas y acceder a cualquier empresa.',
                'level' => 0,
                'permissions' => [
                    'view' => true,
                    'create' => true,
                    'createTypes' => [
                        'daily_close' => true,
                        'expense' => true,
                        'income' => true,
                        'expense_refund' => true,
                    ],
                    'edit' => true,
                    'delete' => true,
                    'export' => true,
                    'settings' => true,
                    'manageUsers' => true,
                    'manageCompanies' => true,
                ],
            ],
            [
                'key' => 'admin',
                'name' => 'Administrador',
                'description' => 'Acceso total dentro de su empresa. Todas las tiendas. Ajustes y administración.',
                'level' => 1,
                'permissions' => [
                    'view' => true,
                    'create' => true,
                    'createTypes' => [
                        'daily_close' => true,
                        'expense' => true,
                        'income' => true,
                        'expense_refund' => true,
                    ],
                    'edit' => true,
                    'delete' => true,
                    'export' => true,
                    'settings' => true,
                    'manageUsers' => true,
                ],
            ],
            [
                'key' => 'manager',
                'name' => 'Manager',
                'description' => 'Solo su tienda. Cierres, control efectivo, objetivos, pedidos, inventario, RR.HH. Crear/editar/borrar. Sin ajustes ni administración.',
                'level' => 2,
                'permissions' => [
                    'view' => true,
                    'create' => true,
                    'createTypes' => [
                        'daily_close' => true,
                        'expense' => true,
                        'income' => true,
                        'expense_refund' => true,
                    ],
                    'edit' => true,
                    'delete' => true,
                    'export' => true,
                    'settings' => false,
                    'manageUsers' => false,
                ],
            ],
            [
                'key' => 'employee',
                'name' => 'Empleado',
                'description' => 'Solo su tienda. Cierres e inventario: crear/editar (no borrar). Objetivos: ver. RR.HH.: solo ver su propia ficha.',
                'level' => 3,
                'permissions' => [
                    'view' => true,
                    'create' => true,
                    'createTypes' => [
                        'daily_close' => true,
                        'expense' => false,
                        'income' => false,
                        'expense_refund' => false,
                    ],
                    'edit' => true,
                    'delete' => false,
                    'export' => false,
                    'settings' => false,
                    'manageUsers' => false,
                ],
            ],
            [
                'key' => 'viewer',
                'name' => 'Visualizador',
                'description' => 'Solo su tienda. Solo visualizar datos. No crear, editar ni borrar.',
                'level' => 4,
                'permissions' => [
                    'view' => true,
                    'create' => false,
                    'createTypes' => [
                        'daily_close' => false,
                        'expense' => false,
                        'income' => false,
                        'expense_refund' => false,
                    ],
                    'edit' => false,
                    'delete' => false,
                    'export' => false,
                    'settings' => false,
                    'manageUsers' => false,
                ],
            ],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(
                ['key' => $data['key']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'level' => $data['level'],
                    'permissions' => $data['permissions'],
                ]
            );
        }
    }
}
