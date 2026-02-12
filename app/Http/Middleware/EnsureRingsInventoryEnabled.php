<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRingsInventoryEnabled
{
    /**
     * Comprueba que el inventario de anillos esté activado para la empresa actual.
     * Si no, redirige con error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = session('company_id');
        if (!$companyId) {
            return redirect()->route('company.select')->with('error', 'Selecciona una empresa.');
        }

        $company = Company::withoutGlobalScopes()->find($companyId);
        if (!$company || !$company->rings_inventory_enabled) {
            return redirect()->route('dashboard')->with('error', 'El inventario de anillos no está activado para esta empresa.');
        }

        return $next($request);
    }
}
