<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySelected
{
    /**
     * Rutas excluidas del middleware (para super_admin sin empresa seleccionada).
     */
    protected array $excludedRoutes = [
        'company.select',
        'company.switch',
        'company.store',
        'logout',
        'login',
    ];

    /**
     * Handle an incoming request.
     *
     * Verifica que:
     * - Si el usuario es super_admin y no tiene empresa seleccionada, redirige a selección.
     * - Si el usuario no es super_admin, su company_id debe coincidir con el de sesión.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, dejar pasar (el middleware auth se encargará)
        if (!$user) {
            return $next($request);
        }

        // Verificar si la ruta actual está excluida
        $currentRoute = $request->route()?->getName();
        if ($currentRoute && in_array($currentRoute, $this->excludedRoutes)) {
            return $next($request);
        }

        // Si es super_admin
        if ($user->isSuperAdmin()) {
            // Verificar que tenga una empresa seleccionada en sesión
            if (!session()->has('company_id')) {
                return redirect()->route('company.select')
                    ->with('info', 'Por favor, selecciona una empresa para continuar.');
            }
        } else {
            // Usuario normal: debe tener company_id asignado
            if (!$user->company_id) {
                // Caso excepcional: usuario sin empresa asignada
                auth()->logout();
                return redirect()->route('login')
                    ->withErrors(['username' => 'Tu cuenta no tiene una empresa asignada. Contacta con el administrador.']);
            }

            // Asegurar que la sesión tenga el company_id correcto
            if (session('company_id') !== $user->company_id) {
                session(['company_id' => $user->company_id]);
            }
        }

        return $next($request);
    }
}
