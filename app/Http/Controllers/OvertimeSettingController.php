<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeSetting;
use App\Models\OvertimeType;
use Illuminate\Http\Request;

class OvertimeSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.overtime.view')->only(['index']);
        $this->middleware('permission:settings.overtime.edit')->only(['update', 'storeType', 'updateType', 'destroyType']);
    }

    /**
     * Tipos de horas extras + cuadro de precios por empleada y tipo.
     */
    public function index()
    {
        $types = OvertimeType::orderBy('sort_order')->orderBy('name')->get();
        $employees = Employee::orderBy('full_name')->get();
        $settings = OvertimeSetting::whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');
        return view('overtime.settings', compact('types', 'employees', 'settings'));
    }

    /**
     * Crear tipo de horas extras.
     */
    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $maxOrder = OvertimeType::max('sort_order') ?? 0;
        OvertimeType::create([
            'name' => $validated['name'],
            'sort_order' => $maxOrder + 1,
        ]);
        return redirect()->route('overtime-settings.index')
            ->with('success', 'Tipo de horas extras creado correctamente.');
    }

    /**
     * Actualizar tipo de horas extras.
     */
    public function updateType(Request $request, OvertimeType $overtimeType)
    {
        $this->ensureCompany($overtimeType);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $overtimeType->update($validated);
        return redirect()->route('overtime-settings.index')
            ->with('success', 'Tipo actualizado correctamente.');
    }

    /**
     * Eliminar tipo de horas extras.
     */
    public function destroyType(OvertimeType $overtimeType)
    {
        $this->ensureCompany($overtimeType);
        $overtimeType->delete();
        return redirect()->route('overtime-settings.index')
            ->with('success', 'Tipo eliminado correctamente.');
    }

    /**
     * Guardar precios (por empleada y tipo).
     */
    public function update(Request $request)
    {
        $employees = Employee::all();
        $types = OvertimeType::all();

        foreach ($employees as $employee) {
            foreach ($types as $type) {
                $key = 'employee_' . $employee->id . '_type_' . $type->id;
                $price = (float) ($request->input($key) ?? 0);
                OvertimeSetting::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'overtime_type_id' => $type->id,
                    ],
                    ['price_per_hour' => $price]
                );
            }
        }
        return redirect()->route('overtime-settings.index')->with('success', 'Precios de horas extras guardados.');
    }

    private function ensureCompany(OvertimeType $overtimeType): void
    {
        $companyId = session('company_id');
        if ($companyId === null || (int) $overtimeType->company_id !== (int) $companyId) {
            abort(403, 'No puedes modificar tipos de otra empresa.');
        }
    }
}
