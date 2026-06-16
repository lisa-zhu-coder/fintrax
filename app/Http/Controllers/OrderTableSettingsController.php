<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\OrderTableSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTableSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user?->isSuperAdmin() || $user?->hasPermission('orders.main.edit')) {
                return $next($request);
            }

            abort(403);
        });
    }

    public function index(): View|RedirectResponse
    {
        $company = OrderTableSettings::company();
        if ($company === null) {
            return redirect()->route('company.select')
                ->with('warning', 'Selecciona una empresa para configurar las tablas de pedidos.');
        }

        return view('settings.orders.table-columns', [
            'company' => $company,
            'tableDefinitions' => OrderTableSettings::tableDefinitions(),
            'tableConfig' => OrderTableSettings::resolveConfig($company),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = OrderTableSettings::company();
        if ($company === null) {
            return redirect()->route('company.select')
                ->with('warning', 'Selecciona una empresa para configurar las tablas de pedidos.');
        }

        $normalized = OrderTableSettings::normalizeInput($request->input('tables', []));

        $company->update([
            'orders_table_settings' => $normalized,
        ]);

        return redirect()
            ->route('order-table-settings.index')
            ->with('success', 'Configuración de columnas guardada correctamente.');
    }
}
