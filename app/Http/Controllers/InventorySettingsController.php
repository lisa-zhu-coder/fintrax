<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class InventorySettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.inventories.view')->only(['index']);
    }

    public function index()
    {
        $company = Company::withoutGlobalScopes()->find(session('company_id'));
        $ringsEnabled = $company?->rings_inventory_enabled ?? false;

        return view('settings.inventories.index', compact('ringsEnabled'));
    }

    /**
     * Toggle inventario de anillos (solo Super Admin).
     */
    public function toggleRings(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Solo el Super Admin puede activar o desactivar el inventario de anillos.');
        }

        $companyId = session('company_id');
        if (!$companyId) {
            return redirect()->back()->with('error', 'No hay empresa seleccionada.');
        }

        $company = Company::withoutGlobalScopes()->find($companyId);
        if (!$company) {
            return redirect()->back()->with('error', 'Empresa no encontrada.');
        }

        $company->update(['rings_inventory_enabled' => !$company->rings_inventory_enabled]);
        $status = $company->rings_inventory_enabled ? 'activado' : 'desactivado';

        return redirect()->route('inventory-settings.index')->with('success', "Inventario de anillos {$status} para esta empresa.");
    }
}
