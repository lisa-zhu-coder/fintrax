<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = \App\Models\Role::class;

    public function definition(): array
    {
        return [
            'key' => 'empleado',
            'name' => 'Empleado',
            'description' => 'Rol de empleado',
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
                'edit' => false,
                'delete' => false,
                'export' => false,
                'settings' => false,
                'manageUsers' => false,
            ],
        ];
    }
}
