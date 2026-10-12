<?php

use App\Models\CompanyRolePermission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Documentos RRHH: unificar "Descargar" en "Ver" y dejar espacio a Editar.
     */
    public function up(): void
    {
        $map = [
            'hr.documents.download' => 'hr.documents.view',
            'rrhh.documents.view' => 'hr.documents.view',
            'rrhh.documents.create' => 'hr.documents.upload',
            'rrhh.documents.edit' => 'hr.documents.edit',
            'rrhh.documents.delete' => 'hr.documents.delete',
        ];

        foreach (Role::withoutGlobalScopes()->get() as $role) {
            $p = $role->permissions ?? [];
            if (! is_array($p)) {
                continue;
            }
            $changed = false;
            foreach ($map as $old => $new) {
                if (! empty($p[$old])) {
                    $p[$new] = true;
                    unset($p[$old]);
                    $changed = true;
                }
            }
            if ($changed) {
                $role->permissions = $p;
                $role->save();
            }
        }

        foreach (CompanyRolePermission::withoutGlobalScopes()->get() as $crp) {
            $p = $crp->permissions ?? [];
            if (! is_array($p)) {
                continue;
            }
            $changed = false;
            foreach ($map as $old => $new) {
                if (! empty($p[$old])) {
                    $p[$new] = true;
                    unset($p[$old]);
                    $changed = true;
                }
            }
            if ($changed) {
                $crp->permissions = $p;
                $crp->save();
            }
        }
    }

    public function down(): void
    {
        //
    }
};
