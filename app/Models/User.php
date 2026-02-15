<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Tiendas a las que tiene acceso este usuario (puede ser más de una).
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'user_store');
    }

    /**
     * Acceso a empresas con rol (y opcionalmente tienda) por empresa.
     * Solo el superadmin gestiona estas asignaciones.
     */
    public function companyAccess()
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role_id', 'store_id')
            ->withTimestamps()
            ->using(CompanyUser::class);
    }

    /**
     * Filas de la tabla company_user (para sincronizar desde el controlador).
     */
    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function financialEntries()
    {
        return $this->hasMany(FinancialEntry::class, 'created_by');
    }

    /**
     * Rol efectivo en la empresa actual (sesión). Con company_user, el rol puede ser distinto por empresa.
     */
    public function getEffectiveRole(): ?Role
    {
        if ($this->isSuperAdmin()) {
            return $this->role;
        }
        $companyId = session('company_id');
        if ($companyId !== null && $companyId !== '') {
            $companyId = (int) $companyId;
            $pivot = \App\Models\CompanyUser::where('user_id', $this->id)
                ->where('company_id', $companyId)
                ->first();
            if ($pivot) {
                return $pivot->role;
            }
        }
        return $this->role;
    }

    /**
     * ID del rol efectivo en la empresa actual.
     */
    public function getEffectiveRoleId(): ?int
    {
        $role = $this->getEffectiveRole();
        return $role ? (int) $role->id : null;
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->getEffectiveRole();
        if (!$role) {
            return false;
        }
        if ($role->key === 'super_admin' || $role->key === 'admin') {
            return true;
        }
        $companyId = session('company_id') ?? $this->company_id;
        return $role->hasPermission($permission, $companyId);
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
        $role = $this->getEffectiveRole();
        return $role && $role->key === 'admin';
    }

    /**
     * Manager: solo su tienda, crear/editar/borrar. Sin ajustes ni administración.
     */
    public function isManager(): bool
    {
        $role = $this->getEffectiveRole();
        return $role && $role->key === 'manager';
    }

    /**
     * Employee: solo su tienda, crear/editar en ciertos módulos (no borrar). RR.HH. solo su ficha.
     */
    public function isEmployee(): bool
    {
        $role = $this->getEffectiveRole();
        return $role && $role->key === 'employee';
    }

    /**
     * Viewer: solo su tienda, solo visualizar.
     */
    public function isViewer(): bool
    {
        $role = $this->getEffectiveRole();
        return $role && $role->key === 'viewer';
    }

    /**
     * @deprecated Usar hasPermission('modulo.submodulo.create') en su lugar.
     */
    public function canCreate(): bool
    {
        $role = $this->getEffectiveRole();
        if (!$role) return false;
        $perms = $role->permissions ?? [];
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
        $role = $this->getEffectiveRole();
        if (!$role) return false;
        $perms = $role->permissions ?? [];
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
        $role = $this->getEffectiveRole();
        if (!$role) return false;
        $perms = $role->permissions ?? [];
        foreach (array_keys($perms) as $key) {
            if ($perms[$key] && str_ends_with($key, '.delete')) {
                return true;
            }
        }
        return false;
    }

    /**
     * IDs de tiendas a las que el usuario tiene acceso (vía user_store o legacy store_id).
     * Admin/Super Admin: array vacío = todas las tiendas de la empresa.
     */
    public function getAllowedStoreIds(): array
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return [];
        }
        $ids = $this->stores()->pluck('stores.id')->map(fn ($id) => (int) $id)->values()->all();
        if (empty($ids) && $this->store_id) {
            return [(int) $this->store_id];
        }
        return $ids;
    }

    /**
     * Store ID al que está limitado el usuario cuando solo tiene UNA tienda.
     * null = admin/super_admin (todas) o usuario con múltiples tiendas (elige en el filtro).
     */
    public function getEnforcedStoreId(): ?int
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return null;
        }
        $allowed = $this->getAllowedStoreIds();
        if (count($allowed) === 1) {
            return $allowed[0];
        }
        return null;
    }

    /**
     * Comprueba si el usuario puede acceder a la tienda.
     * Admin/Super Admin: cualquiera. Resto: solo las de getAllowedStoreIds().
     */
    public function canAccessStore(?int $storeId): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin() || $storeId === null) {
            return true;
        }
        $allowed = $this->getAllowedStoreIds();
        if (empty($allowed)) {
            return false;
        }
        return in_array((int) $storeId, $allowed, true);
    }

    /**
     * IDs de empresas a las que tiene acceso (vía company_user). Super_admin no usa esta tabla.
     */
    public function getCompanyAccessCompanyIds(): array
    {
        return \App\Models\CompanyUser::where('user_id', $this->id)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
