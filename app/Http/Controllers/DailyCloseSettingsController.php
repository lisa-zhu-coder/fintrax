<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyCloseSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.daily_close.view')->only(['index']);
        $this->middleware('permission:settings.daily_close.edit')->only(['update']);
    }

    public function index(): View|RedirectResponse
    {
        $companyId = session('company_id');
        if (!$companyId) {
            return redirect()->route('company.select')
                ->with('warning', 'Selecciona una empresa para configurar el cierre de caja.');
        }

        $company = Company::withoutGlobalScopes()->find($companyId);
        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Empresa no encontrada.');
        }

        return view('settings.daily-close.index', [
            'company' => $company,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = session('company_id');
        if (!$companyId) {
            return redirect()->route('company.select')
                ->with('warning', 'Selecciona una empresa para configurar el cierre de caja.');
        }

        $company = Company::withoutGlobalScopes()->find($companyId);
        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Empresa no encontrada.');
        }

        $validated = $request->validate([
            'daily_close_pos_label' => 'required|string|max:100',
            'daily_close_pos_cash_label' => 'required|string|max:150',
            'daily_close_pos_card_label' => 'required|string|max:150',
            'daily_close_vouchers_enabled' => 'boolean',
        ], [
            'daily_close_pos_label.required' => 'El nombre del apartado POS es obligatorio.',
            'daily_close_pos_cash_label.required' => 'El nombre del campo efectivo es obligatorio.',
            'daily_close_pos_card_label.required' => 'El nombre del campo tarjeta es obligatorio.',
        ]);

        $company->update([
            'daily_close_pos_label' => $validated['daily_close_pos_label'],
            'daily_close_pos_cash_label' => $validated['daily_close_pos_cash_label'],
            'daily_close_pos_card_label' => $validated['daily_close_pos_card_label'],
            'daily_close_vouchers_enabled' => $request->boolean('daily_close_vouchers_enabled'),
        ]);

        return redirect()->route('daily-close-settings.index')
            ->with('success', 'Configuración de cierre de caja guardada correctamente.');
    }
}
