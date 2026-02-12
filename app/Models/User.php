<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use Notifiable;
    
    protected $fillable = [
        'company_id',
        'username',
        'name',
        'email',
        'password',
        'role_id',
        'store_id',
    ];

    /**
     * Empresa a la que pertenece este usuario.
     * Null para super_admin (puede acceder a cualquier empresa).
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function financialEntries()
    {
        return $this->hasMany(FinancialEntry::class, 'created_by');
    }

    public function hasPermission(string $permission): bool
    {
        // Super admin y admin tienen todos los permisos
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }
        
        // Pasar company_id para usar permisos personalizados por empresa
        return $this->role && $this->role->hasPermission($permission, $this->company_id);
    }

    /**
     * Comprueba si el usuario tiene al menos uno de los permisos indicados.
     *
     * @param array<string> $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->hasPermission($p)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Super Admin: acceso total a todas las empresas. No pertenece a ninguna empresa fija.
     * Puede crear empresas, ver todas las empresas y acceder a cualquier empresa.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->key === 'super_admin';
    }

    /**
     * Admin: acceso total dentro de su empresa, puede tener store_id null y ver todas las tiendas de su empresa.
     */
    public function isAdmin(): bool
    {
        return $this->role && $this->role->key === 'admin';
    }

    /**
     * Manager: solo su tienda, crear/editar/borrar. Sin ajustes ni administración.
     */
    public function isManager(): bool
    {
        return $this->role && $this->role->key === 'manager';
    }

    /**
     * Employee: solo su tienda, crear/editar en ciertos módulos (no borrar). RR.HH. solo su ficha.
     */
    public function isEmployee(): bool
    {
        return $this->role && $this->role->key === 'employee';
    }

    /**
     * Viewer: solo su tienda, solo visualizar.
     */
    public function isViewer(): bool
    {
        return $this->role && $this->role->key === 'viewer';
    }

    /**
     * @deprecated Usar hasPermission('modulo.submodulo.create') en su lugar.
     */
    public function canCreate(): bool
    {
        $perms = $this->role->permissions ?? [];
        foreach (array_keys($perms) as $key) {
            if ($perms[$key] && str_ends_with($key, '.create')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @deprecated Usar hasPermission('modulo.submodulo.edit') en su lugar.
     */
    public function canEdit(): bool
    {
        $perms = $this->role->permissions ?? [];
        foreach (array_keys($perms) as $key) {
            if ($perms[$key] && str_ends_with($key, '.edit')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @deprecated Usar hasPermission('modulo.submodulo.delete') en su lugar.
     */
    public function canDelete(): bool
    {
        $perms = $this->role->permissions ?? [];
        foreach (array_keys($perms) as $key) {
            if ($perms[$key] && str_ends_with($key, '.delete')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Store ID al que está limitado el usuario. null = admin/super_admin (todas las tiendas).
     * Para no-admin, SIEMPRE debe usarse este valor para filtrar/crear.
     */
    public function getEnforcedStoreId(): ?int
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return null;
        }
        return $this->store_id ? (int) $this->store_id : null;
    }

    /**
     * Comprueba si el usuario puede acceder a la tienda (super_admin/admin = cualquiera, resto = solo su tienda).
     */
    public function canAccessStore(?int $storeId): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $storeId === null) {
            return true;
        }
        return $this->store_id !== null && (int) $this->store_id === (int) $storeId;
    }
}
