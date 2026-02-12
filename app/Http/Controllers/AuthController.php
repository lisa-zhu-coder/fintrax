<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Si es super_admin sin empresa seleccionada, ir a selección
            if ($user->isSuperAdmin() && !session()->has('company_id')) {
                return redirect()->route('company.select');
            }
            
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Si el usuario es 'admin' y no existe, crearlo automáticamente
        if ($request->username === 'admin' && $request->password === 'admin123') {
            $user = User::where('username', 'admin')->first();
            
            if (!$user) {
                // Buscar o crear el rol admin
                $adminRole = \App\Models\Role::where('key', 'admin')->first();
                
                if (!$adminRole) {
                    // Si no existe el rol, crear roles básicos
                    $adminRole = \App\Models\Role::create([
                        'key' => 'admin',
                        'name' => 'Administrador',
                        'description' => 'Acceso completo a todas las funciones.',
                        'level' => 1,
                        'permissions' => [
                            'view' => true,
                            'create' => true,
                            'createTypes' => [
                                'daily_close' => true,
                                'expense' => true,
                                'income' => true,
                                'expense_refund' => true,
                            ],
                            'edit' => true,
                            'delete' => true,
                            'export' => true,
                            'settings' => true,
                            'manageUsers' => true,
                        ],
                    ]);
                }
                
                // Crear el usuario admin
                $user = User::create([
                    'username' => 'admin',
                    'name' => 'Administrador',
                    'email' => 'admin@miramira.com',
                    'password' => Hash::make('admin123'),
                    'role_id' => $adminRole->id,
                    'store_id' => null,
                ]);
            }
        } else {
            $user = User::where('username', $request->username)->first();
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Obtener el password hash directamente de la base de datos
        $passwordHash = $user->getOriginal('password') ?? $user->password;
        
        if (!Hash::check($request->password, $passwordHash)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        // Si es super_admin, redirigir a selección de empresa
        if ($user->isSuperAdmin()) {
            return redirect()->route('company.select');
        }

        // Para usuarios normales, establecer company_id en sesión
        if ($user->company_id) {
            session(['company_id' => $user->company_id]);
        }

        // Redirigir según permisos: si no tiene acceso al dashboard, ir a cierres de caja
        if ($user->hasPermission('dashboard.main.view')) {
            return redirect()->intended(route('dashboard'));
        } else {
            return redirect()->intended(route('financial.daily-closes'));
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
