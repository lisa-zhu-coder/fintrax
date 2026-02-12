<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use SyncsStoresFromBusinesses;
    
    public function __construct()
    {
        $this->middleware('permission:admin.users.view')->only(['index']);
        $this->middleware('permission:admin.users.create')->only(['create', 'store']);
        $this->middleware('permission:admin.users.edit')->only(['edit', 'update']);
        $this->middleware('permission:admin.users.delete')->only(['destroy']);
    }

    public function index()
    {
        // Sincronizar stores antes de mostrar la lista
        $this->syncStoresFromBusinesses();
        
        $usersQuery = User::with(['role', 'store']);
        $rolesQuery = Role::query();
        
        // Filtrar usuarios por la empresa seleccionada en sesión
        $companyId = session('company_id');
        if ($companyId) {
            $usersQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
                // Si es super_admin, también mostrar otros super_admin (company_id = null)
                if (auth()->user()->isSuperAdmin()) {
                    $q->orWhereNull('company_id');
                }
            });
        }
        
        // Si no es super_admin, ocultar usuarios y roles super_admin
        if (!auth()->user()->isSuperAdmin()) {
            $usersQuery->whereHas('role', function ($q) {
                $q->where('key', '!=', 'super_admin');
            });
            $rolesQuery->where('key', '!=', 'super_admin');
        }
        
        $users = $usersQuery->get();
        $roles = $rolesQuery->get();
        $stores = Store::all();
        
        return view('users.index', compact('users', 'roles', 'stores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'store_id' => 'nullable|exists:stores,id',
        ], [
            'store_id.required' => 'Los usuarios que no son administradores deben tener una tienda asignada.',
        ]);

        $role = Role::find($validated['role_id']);
        
        // Solo super_admin puede crear usuarios con rol super_admin
        if ($role && $role->key === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            return redirect()->back()->withInput()->withErrors(['role_id' => 'No tienes permiso para crear usuarios con rol Super Admin.']);
        }
        
        if ($role && !in_array($role->key, ['admin', 'super_admin']) && empty($validated['store_id'])) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Los usuarios que no son administradores deben tener una tienda asignada.']);
        }

        $validated['password'] = Hash::make($validated['password']);
        if (empty($validated['store_id'])) {
            $validated['store_id'] = null;
        }
        
        // Asignar company_id del usuario actual (o null para super_admin)
        if ($role && $role->key === 'super_admin') {
            $validated['company_id'] = null;
        } else {
            $validated['company_id'] = session('company_id');
        }

        try {
            User::create($validated);
            return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->withInput()
                ->withErrors(['error' => 'Error al crear el usuario: ' . $e->getMessage()]);
        }
    }

    public function show(User $user)
    {
        // Verificar que el usuario pertenece a la empresa actual (o es super_admin sin empresa)
        $companyId = session('company_id');
        if ($companyId && $user->company_id !== null && $user->company_id != $companyId) {
            abort(403, 'No tienes permiso para ver este usuario.');
        }
        
        return response()->json([
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'store_id' => $user->store_id,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Verificar que el usuario pertenece a la empresa actual
        $companyId = session('company_id');
        if ($companyId && $user->company_id !== null && $user->company_id != $companyId) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para editar este usuario.');
        }
        
        // Solo super_admin puede editar usuarios super_admin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para editar usuarios Super Admin.');
        }
        
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $newRole = Role::find($validated['role_id']);
        
        // Solo super_admin puede asignar rol super_admin
        if ($newRole && $newRole->key === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            return redirect()->back()->withInput()->withErrors(['role_id' => 'No tienes permiso para asignar el rol Super Admin.']);
        }
        
        // No permitir quitar el rol super_admin a un usuario super_admin si no eres super_admin
        if ($user->isSuperAdmin() && $newRole && $newRole->key !== 'super_admin' && !auth()->user()->isSuperAdmin()) {
            return redirect()->back()->withInput()->withErrors(['role_id' => 'No tienes permiso para cambiar el rol de un Super Admin.']);
        }
        
        if ($newRole && !in_array($newRole->key, ['admin', 'super_admin']) && empty($validated['store_id'])) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Los usuarios que no son administradores deben tener una tienda asignada.']);
        }

        if (isset($validated['store_id']) && empty($validated['store_id'])) {
            $validated['store_id'] = null;
        }

        try {
            $user->update($validated);
            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->withInput()
                ->withErrors(['error' => 'Error al actualizar el usuario: ' . $e->getMessage()]);
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }
        
        // Verificar que el usuario pertenece a la empresa actual
        $companyId = session('company_id');
        if ($companyId && $user->company_id !== null && $user->company_id != $companyId) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para eliminar este usuario.');
        }
        
        // Solo super_admin puede eliminar usuarios super_admin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para eliminar usuarios Super Admin.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
