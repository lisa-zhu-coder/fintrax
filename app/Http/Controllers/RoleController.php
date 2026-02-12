<?php

namespace App\Http\Controllers;

use App\Models\CompanyRolePermission;
use App\Models\Role;
use App\Support\PermissionDefinitions;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:admin.roles.view')->only(['index']);
        $this->middleware('permission:admin.roles.edit')->only(['create', 'store', 'edit', 'update', 'resetPermissions']);
    }

    public function index()
    {
        $query = Role::orderBy('level');
        
        // Si no es super_admin, no mostrar el rol super_admin
        if (!auth()->user()->isSuperAdmin()) {
            $query->where('key', '!=', 'super_admin');
        }
        
        $roles = $query->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissionModules = PermissionDefinitions::forUi();
        return view('roles.create', compact('permissionModules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:64|unique:roles,key|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'level' => 'required|integer|min:0|max:99',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable',
        ], [
            'key.unique' => 'Esa clave de rol ya existe.',
            'key.regex' => 'La clave solo puede contener letras minúsculas, números y guiones bajos.',
        ]);

        $allKeys = PermissionDefinitions::allKeys();
        $permissions = $request->input('permissions', []);
        $newPermissions = [];
        foreach ($allKeys as $key) {
            $newPermissions[$key] = !empty($permissions[$key]);
        }

        Role::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'level' => (int) $validated['level'],
            'permissions' => $newPermissions,
        ]);

        return redirect()->route('roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function edit(Role $role)
    {
        // Solo super_admin puede editar el rol super_admin
        if ($role->key === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('roles.index')->with('error', 'No tienes permiso para editar el rol Super Admin.');
        }
        
        $permissionModules = PermissionDefinitions::forUi();
        $isSuperAdminRole = $role->key === 'super_admin';
        
        // Cargar permisos efectivos: personalizados por empresa si existen, sino los base del rol
        $companyId = session('company_id');
        $effectivePermissions = $role->getEffectivePermissions($companyId);
        
        return view('roles.edit', compact('role', 'permissionModules', 'isSuperAdminRole', 'effectivePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        // Solo super_admin puede editar el rol super_admin
        if ($role->key === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('roles.index')->with('error', 'No tienes permiso para editar el rol Super Admin.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable',
        ]);

        $allKeys = PermissionDefinitions::allKeys();
        $permissions = $request->input('permissions', []);
        $newPermissions = [];
        foreach ($allKeys as $key) {
            $newPermissions[$key] = !empty($permissions[$key]);
        }

        $companyId = session('company_id');
        
        if ($companyId) {
            // Guardar permisos en company_role_permissions (solo afecta a esta empresa)
            CompanyRolePermission::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'role_id' => $role->id,
                ],
                ['permissions' => $newPermissions]
            );
            // No actualizar role->permissions (son los valores por defecto globales)
        } else {
            // Sin empresa en sesión (caso raro): actualizar permisos base del rol
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'permissions' => $newPermissions,
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'Permisos del rol actualizados correctamente para esta empresa.');
    }

    /**
     * Restaura los permisos por defecto del rol para la empresa actual.
     */
    public function resetPermissions(Role $role)
    {
        if ($role->key === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('roles.index')->with('error', 'No tienes permiso para editar el rol Super Admin.');
        }

        $companyId = session('company_id');
        if (!$companyId) {
            return redirect()->route('roles.index')->with('error', 'No hay empresa seleccionada.');
        }

        CompanyRolePermission::where('company_id', $companyId)
            ->where('role_id', $role->id)
            ->delete();

        return redirect()->route('roles.edit', $role)->with('success', 'Permisos restaurados a los valores por defecto del rol.');
    }
}
