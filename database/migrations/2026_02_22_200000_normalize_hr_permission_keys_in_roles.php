<?php

use App\Models\CompanyRolePermission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'hr.payroll.payroll.view' => 'hr.payroll.view',
            'hr.payroll.payroll.send' => 'hr.payroll.send',
            'hr.payroll.payroll.upload' => 'hr.payroll.upload',
            'hr.payroll.payroll.delete' => 'hr.payroll.delete',
            'payroll.view' => 'hr.payroll.view',
            'payroll.send' => 'hr.payroll.send',
            'payroll.create' => 'hr.payroll.upload',
            'payroll.upload' => 'hr.payroll.upload',
            'payroll.delete' => 'hr.payroll.delete',
            'hr.documents.view' => 'hr.documents.download',
            'rrhh.documents.view' => 'hr.documents.download',
            'rrhh.documents.create' => 'hr.documents.upload',
        ];

        foreach (Role::withoutGlobalScopes()->get() as $role) {
            $p = $role->permissions ?? [];
            if (! is_array($p)) {
                continue;
            }
            foreach ($map as $old => $new) {
                if (! empty($p[$old])) {
                    $p[$new] = true;
                    unset($p[$old]);
                }
            }
            $role->permissions = $p;
            $role->save();
        }

        foreach (CompanyRolePermission::withoutGlobalScopes()->get() as $crp) {
            $p = $crp->permissions ?? [];
            if (! is_array($p)) {
                continue;
            }
            foreach ($map as $old => $new) {
                if (! empty($p[$old])) {
                    $p[$new] = true;
                    unset($p[$old]);
                }
            }
            $crp->permissions = $p;
            $crp->save();
        }
    }

    public function down(): void
    {
        //
    }
};
