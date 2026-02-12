<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\OvertimeSetting;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    use EnforcesStoreScope;

    public function __construct()
    {
        $this->middleware('permission:hr.overtime.view')->only(['index', 'storeMonths', 'monthDetail', 'employeeDetail']);
        $this->middleware('permission:hr.overtime.create')->only(['create', 'store']);
        $this->middleware('permission:hr.overtime.edit')->only(['editRecord', 'updateRecord']);
        $this->middleware('permission:hr.overtime.delete')->only(['destroyRecord']);
    }

    /**
     * Vista 1: Tiendas (con año opcional).
     */
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $stores = $this->storesForCurrentUser();
        $availableYears = $this->availableYears();
        return view('overtime.index', compact('stores', 'year', 'availableYears'));
    }

    /**
     * Vista 2: Meses de una tienda (12 meses con resumen).
     */
    public function storeMonths(Store $store, int $year)
    {
        $employeeIds = $store->employees()->pluck('employees.id');
        $records = OvertimeRecord::whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->get();
        $monthsData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthRecords = $records->filter(fn ($r) => (int) $r->date->format('n') === $month);
            $totalOvertime = $monthRecords->sum('overtime_hours');
            $totalSundayHoliday = $monthRecords->sum('sunday_holiday_hours');
            $amountOvertime = 0;
            $amountSundayHoliday = 0;
            foreach ($monthRecords->groupBy('employee_id') as $empId => $empRecords) {
                [$priceOvertime, $priceSunday] = OvertimeSetting::getPriceForEmployee($empId);
                $amountOvertime += $empRecords->sum('overtime_hours') * $priceOvertime;
                $amountSundayHoliday += $empRecords->sum('sunday_holiday_hours') * $priceSunday;
            }
            $monthsData[] = (object) [
                'month' => $month,
                'monthName' => Carbon::createFromDate($year, $month, 1)->locale('es')->monthName,
                'year' => $year,
                'total_overtime_hours' => $totalOvertime,
                'total_sunday_holiday_hours' => $totalSundayHoliday,
                'total_amount_overtime' => $amountOvertime,
                'total_amount_sunday_holiday' => $amountSundayHoliday,
            ];
        }
        return view('overtime.store-months', compact('store', 'year', 'monthsData'));
    }

    /**
     * Vista 3: Empleadas del mes (tabla por empleada con totales).
     */
    public function monthDetail(Store $store, int $year, int $month)
    {
        $this->authorizeStoreAccess($store->id);
        $monthStr = sprintf('%04d-%02d', $year, $month);
        $start = Carbon::parse($year . '-' . $month . '-01');
        $end = $start->copy()->endOfMonth();
        $employeeIds = $store->employees()->pluck('employees.id');
        $records = OvertimeRecord::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();
        $byEmployee = $records->groupBy('employee_id');
        $employeesData = [];
        foreach ($byEmployee as $empId => $empRecords) {
            $employee = Employee::find($empId);
            if (! $employee) {
                continue;
            }
            [$priceOvertime, $priceSunday] = OvertimeSetting::getPriceForEmployee($empId);
            $hoursOvertime = $empRecords->sum('overtime_hours');
            $hoursSundayHoliday = $empRecords->sum('sunday_holiday_hours');
            $amountOvertime = $hoursOvertime * $priceOvertime;
            $amountSundayHoliday = $hoursSundayHoliday * $priceSunday;
            $employeesData[] = (object) [
                'employee' => $employee,
                'hours_overtime' => $hoursOvertime,
                'price_overtime' => $priceOvertime,
                'amount_overtime' => $amountOvertime,
                'hours_sunday_holiday' => $hoursSundayHoliday,
                'price_sunday_holiday' => $priceSunday,
                'amount_sunday_holiday' => $amountSundayHoliday,
            ];
        }
        usort($employeesData, fn ($a, $b) => strcmp($a->employee->full_name ?? '', $b->employee->full_name ?? ''));
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('es')->monthName;
        $employees = $store->employees()->orderBy('full_name')->get();
        return view('overtime.month', compact('store', 'year', 'month', 'monthName', 'employeesData', 'employees'));
    }

    /**
     * Formulario añadir horas.
     */
    public function create(Store $store, int $year, int $month)
    {
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('es')->monthName;
        $employees = $store->employees()->orderBy('full_name')->get();
        return view('overtime.create', compact('store', 'year', 'month', 'monthName', 'employees'));
    }

    /**
     * Guardar nuevo registro de horas.
     */
    public function store(Request $request, Store $store, int $year, int $month)
    {
        $this->authorizeStoreAccess($store->id);
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'overtime_hours' => 'nullable|numeric|min:0',
            'sunday_holiday_hours' => 'nullable|numeric|min:0',
        ], [], [
            'employee_id' => 'empleada',
            'date' => 'fecha',
        ]);
        $employeeId = (int) $validated['employee_id'];
        $belongsToStore = $store->employees()->where('employees.id', $employeeId)->exists();
        if (! $belongsToStore) {
            return redirect()->back()->with('error', 'La empleada no pertenece a esta tienda.');
        }
        OvertimeRecord::create([
            'employee_id' => $employeeId,
            'date' => $validated['date'],
            'overtime_hours' => (float) ($validated['overtime_hours'] ?? 0),
            'sunday_holiday_hours' => (float) ($validated['sunday_holiday_hours'] ?? 0),
        ]);
        return redirect()->route('overtime.month', ['store' => $store, 'year' => $year, 'month' => $month])
            ->with('success', 'Horas añadidas correctamente.');
    }

    /**
     * Historial completo de una empleada.
     */
    public function employeeDetail(Employee $employee)
    {
        $records = $employee->overtimeRecords()->orderByDesc('date')->get();
        [$priceOvertime, $priceSunday] = OvertimeSetting::getPriceForEmployee($employee->id);
        $rows = $records->map(function ($r) use ($priceOvertime, $priceSunday) {
            $amtOvertime = (float) $r->overtime_hours * $priceOvertime;
            $amtSunday = (float) $r->sunday_holiday_hours * $priceSunday;
            return (object) [
                'record' => $r,
                'amount_overtime' => $amtOvertime,
                'amount_sunday_holiday' => $amtSunday,
                'amount_total' => $amtOvertime + $amtSunday,
            ];
        });
        return view('overtime.employee', compact('employee', 'rows', 'priceOvertime', 'priceSunday'));
    }

    /**
     * Editar registro.
     */
    public function editRecord(OvertimeRecord $overtimeRecord)
    {
        $employee = $overtimeRecord->employee;
        $store = $employee->stores()->first();
        if ($store) {
            $this->authorizeStoreAccess($store->id);
        }
        return view('overtime.edit-record', compact('overtimeRecord', 'employee', 'store'));
    }

    /**
     * Actualizar registro.
     */
    public function updateRecord(Request $request, OvertimeRecord $overtimeRecord)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'overtime_hours' => 'nullable|numeric|min:0',
            'sunday_holiday_hours' => 'nullable|numeric|min:0',
        ]);
        $overtimeRecord->update([
            'date' => $validated['date'],
            'overtime_hours' => (float) ($validated['overtime_hours'] ?? 0),
            'sunday_holiday_hours' => (float) ($validated['sunday_holiday_hours'] ?? 0),
        ]);
        return redirect()->route('overtime.employee', $overtimeRecord->employee)
            ->with('success', 'Registro actualizado.');
    }

    /**
     * Eliminar registro.
     */
    public function destroyRecord(OvertimeRecord $overtimeRecord)
    {
        $store = $overtimeRecord->employee->stores()->first();
        if ($store) {
            $this->authorizeStoreAccess($store->id);
        }
        $employee = $overtimeRecord->employee;
        $overtimeRecord->delete();
        return redirect()->route('overtime.employee', $employee)->with('success', 'Registro eliminado.');
    }

    private function availableYears(): \Illuminate\Support\Collection
    {
        $years = OvertimeRecord::query()
            ->get()
            ->pluck('date')
            ->map(fn ($d) => (int) $d->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();
        if ($years->isEmpty() || ! $years->contains(now()->year)) {
            $years = $years->prepend(now()->year)->unique()->sortDesc()->values();
        }
        return $years;
    }
}
