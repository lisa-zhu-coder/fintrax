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
     * Si la empresa tiene personalización, usa esa; si no, usa los permisos base del rol.
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
        
        // Verificar permiso exacto primero
        if (isset($permissions[$permission]) && $permissions[$permission] === true) {
            return true;
        }

        // Compatibilidad: hr.employees.view (legacy) o permiso general 'view' = ver empleados
        if ($permission === 'hr.employees.view_store' && (!empty($permissions['hr.employees.view']) || !empty($permissions['view']))) {
            return true;
        }
        if ($permission === 'hr.employees.view_own' && (!empty($permissions['hr.employees.view']) || !empty($permissions['view']))) {
            return true;
        }
        
        // Mapeo de permisos granulares a permisos generales (compatibilidad)
        // Si el permiso termina en .view, .create, .edit, .delete, verificar el permiso general
        $parts = explode('.', $permission);
        $action = end($parts); // view, create, edit, delete, export
        
        // Mapear acciones a permisos generales
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
