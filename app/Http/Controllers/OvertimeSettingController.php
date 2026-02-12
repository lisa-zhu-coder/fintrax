<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeSetting;
use Illuminate\Http\Request;

class OvertimeSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.overtime.view')->only(['index']);
        $this->middleware('permission:settings.overtime.edit')->only(['update']);
    }

    /**
     * Listado de empleadas con precios por hora extra y hora domingo/festivo.
     */
    public function index()
    {
        $employees = Employee::orderBy('full_name')->get();
        $settings = OvertimeSetting::whereIn('employee_id', $employees->pluck('id'))->get()->keyBy('employee_id');
        return view('overtime.settings', compact('employees', 'settings'));
    }

    /**
     * Guardar precios (por empleada).
     */
    public function update(Request $request)
    {
        $employees = Employee::all();
        foreach ($employees as $employee) {
            $key = 'employee_' . $employee->id;
            $priceOvertime = $request->input($key . '_overtime');
            $priceSunday = $request->input($key . '_sunday');
            OvertimeSetting::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'price_overtime_hour' => (float) ($priceOvertime ?? 0),
                    'price_sunday_holiday_hour' => (float) ($priceSunday ?? 0),
                ]
            );
        }
        return redirect()->route('overtime-settings.index')->with('success', 'Precios de horas extras guardados.');
    }
}
