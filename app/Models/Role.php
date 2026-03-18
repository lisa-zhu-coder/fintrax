<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'level',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Permisos personalizados por empresa para este rol.
     */
    public function companyPermissions(): HasMany
    {
        return $this->hasMany(CompanyRolePermission::class, 'role_id');
    }

    /**
     * Obtiene los permisos efectivos para una empresa.
     */
    public function getEffectivePermissions(?int $companyId = null): array
    {
        if ($companyId) {
            $override = CompanyRolePermission::where('company_id', $companyId)
                ->where('role_id', $this->id)
                ->first();
            if ($override && is_array($override->permissions)) {
                return $override->permissions;
            }
        }

        return $this->permissions ?? [];
    }

    public function hasPermission(string $permission, ?int $companyId = null): bool
    {
        $permissions = $this->getEffectivePermissions($companyId);

        if (isset($permissions[$permission]) && $permissions[$permission] === true) {
            return true;
        }

        // Documentos RRHH: claves antiguas y alias
        if ($permission === 'hr.documents.download') {
            if (! empty($permissions['hr.documents.download']) || ! empty($permissions['hr.documents.view'])
                || ! empty($permissions['rrhh.documents.view'])) {
                return true;
            }
        }
        if ($permission === 'hr.documents.upload') {
            if (! empty($permissions['hr.documents.upload']) || ! empty($permissions['rrhh.documents.create'])) {
                return true;
            }
        }
        if ($permission === 'hr.documents.delete' && ! empty($permissions['rrhh.documents.delete'])) {
            return true;
        }

        // Nóminas: hr.payroll.* y claves antiguas payroll.* / hr.payroll.payroll.*
        $payrollMap = [
            'hr.payroll.view' => ['hr.payroll.view', 'payroll.view', 'hr.payroll.payroll.view'],
            'hr.payroll.send' => ['hr.payroll.send', 'payroll.send', 'hr.payroll.payroll.send'],
            'hr.payroll.upload' => ['hr.payroll.upload', 'payroll.create', 'hr.payroll.payroll.upload', 'payroll.upload'],
            'hr.payroll.delete' => ['hr.payroll.delete', 'payroll.delete', 'hr.payroll.payroll.delete'],
        ];
        if (isset($payrollMap[$permission])) {
            foreach ($payrollMap[$permission] as $key) {
                if (! empty($permissions[$key])) {
                    return true;
                }
            }
            if ($permission === 'hr.payroll.upload' && ! empty($permissions['hr.employees.configure'])) {
                return true;
            }
        }

        if ($permission === 'hr.employees.archived_restore' && (! empty($permissions['hr.employees.edit']) || ! empty($permissions['hr.employees.delete']))) {
            return true;
        }

        if ($permission === 'hr.employees.archived_permanent_delete' && ! empty($permissions['hr.employees.delete'])) {
            return true;
        }

        if ($permission === 'hr.employees.view_store' && (! empty($permissions['hr.employees.view']) || ! empty($permissions['view']))) {
            return true;
        }
        if ($permission === 'hr.employees.view_own' && (! empty($permissions['hr.employees.view']) || ! empty($permissions['view']))) {
            return true;
        }
        if (($permission === 'hr.overtime.view_store' || $permission === 'hr.overtime.view_own') && ! empty($permissions['hr.overtime.view'])) {
            return true;
        }
        if (($permission === 'hr.employees.view_salary_store' || $permission === 'hr.employees.view_salary_own')
            && (! empty($permissions['hr.employees.view_gross_salary']) || ! empty($permissions['hr.employees.view_net_salary']))) {
            return true;
        }

        $parts = explode('.', $permission);
        $action = end($parts);

        $generalPermissionMap = [
            'view' => 'view',
            'create' => 'create',
            'edit' => 'edit',
            'delete' => 'delete',
            'export' => 'export',
        ];

        if (isset($generalPermissionMap[$action])) {
            $generalPermission = $generalPermissionMap[$action];
            if (isset($permissions[$generalPermission]) && $permissions[$generalPermission] === true) {
                return true;
            }
        }

        return false;
    }
}
