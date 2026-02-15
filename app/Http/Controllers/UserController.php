<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\Company;
use App\Models\CompanyUser;
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
        $this->syncStoresFromBusinesses();
        
        $usersQuery = User::with(['role', 'store']);
        $rolesQuery = Role::query();
        
        $companyId = session('company_id');
        if ($companyId) {
            $usersQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
                if (auth()->user()->isSuperAdmin()) {
                    $q->orWhereNull('company_id');
                }
                // Usuarios con acceso a esta empresa vía company_user
                $q->orWhereHas('companyUsers', function ($q2) use ($companyId) {
                    $q2->where('company_id', $companyId);
                });
            });
        }
        
        if (!auth()->user()->isSuperAdmin()) {
            $usersQuery->whereHas('role', function ($q) {
                $q->where('key', '!=', 'super_admin');
            });
            $rolesQuery->where('key', '!=', 'super_admin');
        }
        
        $users = $usersQuery->get();
        $roles = $rolesQuery->get();
        $stores = Store::all();
        $companies = auth()->user()->isSuperAdmin() ? Company::orderBy('name')->get() : collect();
        
        return view('users.index', compact('users', 'roles', 'stores', 'companies'));
    }

    public function store(Request $request)
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $rules = [
            'username' => 'required|string|max:255|unique:users',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'store_id' => 'nullable|exists:stores,id',
        ];
        if ($isSuperAdmin) {
            $rules['company_access'] = 'nullable|array';
            $rules['company_access.*.company_id'] = 'required|exists:companies,id';
            $rules['company_access.*.role_id'] = 'required|exists:roles,id';
            $rules['company_access.*.store_id'] = 'nullable|exists:stores,id';
        }
        $validated = $request->validate($rules, [
            'store_id.required' => 'Los usuarios que no son administradores deben tener una tienda asignada.',
        ]);

        $role = Role::find($validated['role_id']);
        
        if ($role && $role->key === 'super_admin' && !$isSuperAdmin) {
            return redirect()->back()->withInput()->withErrors(['role_id' => 'No tienes permiso para crear usuarios con rol Super Admin.']);
        }
        
        if ($role && !in_array($role->key, ['admin', 'super_admin']) && empty($validated['store_id'])) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Los usuarios que no son administradores deben tener una tienda asignada.']);
        }

        $validated['password'] = Hash::make($validated['password']);
        if (empty($validated['store_id'])) {
            $validated['store_id'] = null;
        }
        
        if ($role && $role->key === 'super_admin') {
            $validated['company_id'] = null;
        } elseif ($isSuperAdmin && !empty($validated['company_access'])) {
            $first = $validated['company_access'][0] ?? null;
            $validated['company_id'] = $first ? (int) $first['company_id'] : session('company_id');
            $validated['role_id'] = $first ? (int) $first['role_id'] : $validated['role_id'];
            $validated['store_id'] = $first && !empty($first['store_id']) ? (int) $first['store_id'] : null;
        } else {
            $validated['company_id'] = session('company_id');
        }

        try {
            $user = User::create($validated);
            if ($isSuperAdmin && !empty($validated['company_access']) && $role && $role->key !== 'super_admin') {
                $sync = [];
                foreach ($validated['company_access'] as $row) {
                    $sync[(int) $row['company_id']] = [
                        'role_id' => (int) $row['role_id'],
                        'store_id' => !empty($row['store_id']) ? (int) $row['store_id'] : null,
                    ];
                }
                $user->companyAccess()->sync($sync);
            } elseif (!$user->isSuperAdmin() && $user->company_id) {
                CompanyUser::firstOrCreate(
                    ['user_id' => $user->id, 'company_id' => $user->company_id],
                    ['role_id' => $user->role_id, 'store_id' => $user->store_id]
                );
            }
            return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->withInput()
                ->withErrors(['error' => 'Error al crear el usuario: ' . $e->getMessage()]);
        }
    }

    public function show(User $user)
    {
        $companyId = session('company_id');
        $canSee = $user->company_id == $companyId
            || $user->company_id === null
            || CompanyUser::where('user_id', $user->id)->where('company_id', $companyId)->exists();
        if ($companyId && !$canSee) {
            abort(403, 'No tienes permiso para ver este usuario.');
        }
        
        $data = [
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'store_id' => $user->store_id,
        ];
        if (auth()->user()->isSuperAdmin()) {
            $data['company_access'] = $user->companyAccess()->get()->map(function ($company) {
                return [
                    'company_id' => $company->id,
                    'role_id' => $company->pivot->role_id,
                    'store_id' => $company->pivot->store_id,
                ];
            })->values()->all();
        }
        return response()->json($data);
    }

    public function update(Request $request, User $user)
    {
        $companyId = session('company_id');
        $canEdit = $user->company_id == $companyId
            || $user->company_id === null
            || CompanyUser::where('user_id', $user->id)->where('company_id', $companyId)->exists();
        if ($companyId && !$canEdit) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para editar este usuario.');
        }
        
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para editar usuarios Super Admin.');
        }
        
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $rules = [
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'store_id' => 'nullable|exists:stores,id',
        ];
        if ($isSuperAdmin) {
            $rules['company_access'] = 'nullable|array';
            $rules['company_access.*.company_id'] = 'required|exists:companies,id';
            $rules['company_access.*.role_id'] = 'required|exists:roles,id';
            $rules['company_access.*.store_id'] = 'nullable|exists:stores,id';
        }
        $validated = $request->validate($rules);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $newRole = Role::find($validated['role_id']);
        
        if ($newRole && $newRole->key === 'super_admin' && !$isSuperAdmin) {
            return redirect()->back()->withInput()->withErrors(['role_id' => 'No tienes permiso para asignar el rol Super Admin.']);
        }
        
        if ($user->isSuperAdmin() && $newRole && $newRole->key !== 'super_admin' && !$isSuperAdmin) {
            return redirect()->back()->withInput()->withErrors(['role_id' => 'No tienes permiso para cambiar el rol de un Super Admin.']);
        }
        
        if ($newRole && !in_array($newRole->key, ['admin', 'super_admin']) && empty($validated['store_id'])) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Los usuarios que no son administradores deben tener una tienda asignada.']);
        }

        if (isset($validated['store_id']) && empty($validated['store_id'])) {
            $validated['store_id'] = null;
        }

        if ($isSuperAdmin && $user->isSuperAdmin() === false && isset($validated['company_access']) && is_array($validated['company_access'])) {
            $first = $validated['company_access'][0] ?? null;
            if ($first) {
                $validated['company_id'] = (int) $first['company_id'];
                $validated['role_id'] = (int) $first['role_id'];
                $validated['store_id'] = !empty($first['store_id']) ? (int) $first['store_id'] : null;
            }
        }

        try {
            $user->update($validated);
            if ($isSuperAdmin && $user->isSuperAdmin() === false && isset($validated['company_access'])) {
                $sync = [];
                foreach ($validated['company_access'] as $row) {
                    $sync[(int) $row['company_id']] = [
                        'role_id' => (int) $row['role_id'],
                        'store_id' => !empty($row['store_id']) ? (int) $row['store_id'] : null,
                    ];
                }
                $user->companyAccess()->sync($sync);
            }
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
        
        $companyId = session('company_id');
        $canSee = $user->company_id == $companyId
            || $user->company_id === null
            || CompanyUser::where('user_id', $user->id)->where('company_id', $companyId)->exists();
        if ($companyId && !$canSee) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para eliminar este usuario.');
        }
        
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return redirect()->route('users.index')->with('error', 'No tienes permiso para eliminar usuarios Super Admin.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
