<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleSettingsController extends Controller
{
    /**
     * Solo SuperAdmin puede ver y modificar la activación de módulos.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isSuperAdmin()) {
                abort(403, 'No autorizado. Solo el Super Administrador puede acceder a esta sección.');
            }
            return $next($request);
        });
    }

    /**
     * Muestra la página de configuración de módulos (toggle Clientes).
     */
    public function index(): View|RedirectResponse
    {
        $companyId = session('company_id');
        if (!$companyId) {
            return redirect()->route('company.select')
                ->with('warning', 'Selecciona una empresa para configurar los módulos.');
        }

        $company = Company::withoutGlobalScopes()->find($companyId);
        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Empresa no encontrada.');
        }

        return view('settings.modules.index', [
            'company' => $company,
        ]);
    }

    /**
     * Actualiza la configuración de módulos (toggle Clientes).
     */
    public function update(Request $request): RedirectResponse
    {
        $companyId = session('company_id');
        if (!$companyId) {
            return redirect()->route('company.select')
                ->with('warning', 'Selecciona una empresa para configurar los módulos.');
        }

        $company = Company::withoutGlobalScopes()->find($companyId);
        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Empresa no encontrada.');
        }

        $company->update([
            'clients_module_enabled' => $request->boolean('clients_module_enabled'),
        ]);

        return redirect()->route('module-settings.index')
            ->with('success', 'Configuración de módulos guardada correctamente.');
    }
}
