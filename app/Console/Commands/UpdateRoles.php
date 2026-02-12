<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Support\PermissionDefinitions;
use Illuminate\Console\Command;

class UpdateRoles extends Command
{
    protected $signature = 'roles:update';
    protected $description = 'Actualiza roles y permisos por módulo (modulo.submodulo.accion)';

    public function handle()
    {
        $this->info('Actualizando roles con permisos por módulo...');

        $allKeys = PermissionDefinitions::allKeys();
        $byModule = PermissionDefinitions::keysByModule();

        // Admin: todos los permisos
        $adminPermissions = array_fill_keys($allKeys, true);
        Role::updateOrCreate(
            ['key' => 'admin'],
            [
                'name' => 'Administrador',
                'description' => 'Acceso total. Todas las tiendas. Todos los módulos y acciones.',
                'level' => 1,
                'permissions' => $adminPermissions,
            ]
        );
        $this->info('✓ Rol admin actualizado');

        // Manager: todo excepto ajustes y administración (solo su tienda)
        $managerPermissions = $this->managerPermissionSet($allKeys, $byModule);
        Role::updateOrCreate(
            ['key' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Solo su tienda. Finanzas, tesorería, objetivos, pedidos, inventario, RR.HH. Crear/editar/borrar. Sin ajustes ni administración.',
                'level' => 2,
                'permissions' => $managerPermissions,
            ]
        );
        $this->info('✓ Rol manager actualizado');

        // Employee: ver + crear/editar en cierres, inventario, horas extras; RR.HH. solo ver (y en controller solo su ficha)
        $employeePermissions = $this->employeePermissionSet($allKeys, $byModule);
        Role::updateOrCreate(
            ['key' => 'employee'],
            [
                'name' => 'Empleado',
                'description' => 'Solo su tienda. Cierres e inventario crear/editar. RR.HH. solo ver su ficha. Horas extras ver/crear/editar. Sin borrar ni ajustes.',
                'level' => 3,
                'permissions' => $employeePermissions,
            ]
        );
        $this->info('✓ Rol employee actualizado');

        // Viewer: solo ver en los módulos operativos
        $viewerPermissions = $this->viewerPermissionSet($allKeys, $byModule);
        Role::updateOrCreate(
            ['key' => 'viewer'],
            [
                'name' => 'Visor',
                'description' => 'Solo su tienda. Solo visualizar. No crear, editar ni borrar.',
                'level' => 4,
                'permissions' => $viewerPermissions,
            ]
        );
        $this->info('✓ Rol viewer actualizado');

        // Migrar usuarios de roles antiguos si existieran
        $employeeRole = Role::where('key', 'employee')->first();
        $viewerRole = Role::where('key', 'viewer')->first();
        $oldEmpleado = Role::where('key', 'empleado')->first();
        $oldVisor = Role::where('key', 'visor')->first();
        if ($oldEmpleado && $employeeRole) {
            User::where('role_id', $oldEmpleado->id)->update(['role_id' => $employeeRole->id]);
            $oldEmpleado->delete();
            $this->info('✓ Usuarios migrados de empleado a employee');
        }
        if ($oldVisor && $viewerRole) {
            User::where('role_id', $oldVisor->id)->update(['role_id' => $viewerRole->id]);
            $oldVisor->delete();
            $this->info('✓ Usuarios migrados de visor a viewer');
        }

        $this->info('');
        $this->info('✅ Roles actualizados. Permisos: modulo.submodulo.accion');
        return 0;
    }

    private function managerPermissionSet(array $allKeys, array $byModule): array
    {
        $perms = array_fill_keys($allKeys, false);
        $grant = [
            'dashboard', 'financial', 'treasury', 'objectives', 'declared_sales',
            'invoices', 'orders', 'inventory', 'sales', 'hr',
        ];
        foreach ($grant as $moduleKey) {
            foreach ($byModule[$moduleKey] ?? [] as $key) {
                $perms[$key] = true;
            }
        }
        return $perms;
    }

    private function employeePermissionSet(array $allKeys, array $byModule): array
    {
        $perms = array_fill_keys($allKeys, false);
        // Dashboard
        foreach ($byModule['dashboard'] ?? [] as $key) {
            $perms[$key] = true;
        }
        // Finanzas: solo registros (view, create, edit) y daily_closes (view, create, edit) — sin income/expenses create, sin delete, sin export
        $grantFinancial = [
            'financial.registros.view', 'financial.registros.create', 'financial.registros.edit',
            'financial.income.view', 'financial.expenses.view',
            'financial.daily_closes.view', 'financial.daily_closes.create', 'financial.daily_closes.edit',
        ];
        foreach ($grantFinancial as $key) {
            if (isset($perms[$key])) {
                $perms[$key] = true;
            }
        }
        // Objetivos: solo ver
        foreach ($byModule['objectives'] ?? [] as $key) {
            if (str_ends_with($key, '.view')) {
                $perms[$key] = true;
            }
        }
        // Inventario: rings + products view, create, edit (no delete)
        $grantInventory = [
            'inventory.rings.view', 'inventory.rings.create', 'inventory.rings.edit',
            'inventory.products.view', 'inventory.products.create', 'inventory.products.edit',
        ];
        foreach ($grantInventory as $key) {
            if (isset($perms[$key])) {
                $perms[$key] = true;
            }
        }
        // Venta de productos: view, create, edit
        foreach ($byModule['sales'] ?? [] as $key) {
            if (in_array(substr(strrchr($key, '.'), 1), ['view', 'create', 'edit'])) {
                $perms[$key] = true;
            }
        }
        // RR.HH.: solo view (en controller se restringe employee a su ficha) + vacaciones view/edit
        $grantHr = ['hr.employees.view', 'hr.overtime.view', 'hr.overtime.create', 'hr.overtime.edit', 'hr.vacations.view', 'hr.vacations.edit'];
        foreach ($grantHr as $key) {
            if (isset($perms[$key])) {
                $perms[$key] = true;
            }
        }
        return $perms;
    }

    private function viewerPermissionSet(array $allKeys, array $byModule): array
    {
        $perms = array_fill_keys($allKeys, false);
        $viewOnlyModules = [
            'dashboard', 'financial', 'treasury', 'objectives', 'declared_sales',
            'invoices', 'orders', 'inventory', 'sales', 'hr',
        ];
        foreach ($viewOnlyModules as $moduleKey) {
            foreach ($byModule[$moduleKey] ?? [] as $key) {
                if (str_ends_with($key, '.view')) {
                    $perms[$key] = true;
                }
            }
        }
        return $perms;
    }
}
