<?php

namespace App\Http\Middleware;

use App\Models\CompanyUser;
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
     * - Si el usuario no es super_admin: sesión debe ser una de sus empresas (company_user o company_id).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $currentRoute = $request->route()?->getName();
        if ($currentRoute && in_array($currentRoute, $this->excludedRoutes)) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            if (!session()->has('company_id')) {
                return redirect()->route('company.select')
                    ->with('info', 'Por favor, selecciona una empresa para continuar.');
            }
        } else {
            $companyIds = $user->getCompanyAccessCompanyIds();
            $hasCompanyAccess = count($companyIds) > 0;
            $fallbackCompanyId = $user->company_id;

            if (!$hasCompanyAccess && !$fallbackCompanyId) {
                auth()->logout();
                return redirect()->route('login')
                    ->withErrors(['username' => 'Tu cuenta no tiene una empresa asignada. Contacta con el administrador.']);
            }

            $validCompanyIds = $hasCompanyAccess ? $companyIds : [$fallbackCompanyId];
            $sessionCompanyId = session('company_id');

            if (!in_array((int) $sessionCompanyId, array_map('intval', $validCompanyIds), true)) {
                session(['company_id' => $validCompanyIds[0]]);
            }
        }

        return $next($request);
    }
}
