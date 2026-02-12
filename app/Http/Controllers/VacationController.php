<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\Employee;
use App\Models\EmployeeVacationDay;
use App\Models\EmployeeVacationPeriod;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VacationController extends Controller
{
    use EnforcesStoreScope;

    public function __construct()
    {
        $this->middleware('permission:hr.vacations.view')->only(['index', 'storeView', 'calendarMonths', 'calendarMonth']);
        $this->middleware('permission:hr.vacations.edit')->only(['updatePeriods', 'toggleDay', 'registerWeeks']);
    }

    /**
     * Vista 1: Tiendas con filtro año obligatorio.
     */
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $stores = $this->storesForCurrentUser();
        $availableYears = range(now()->year, now()->year - 5);
        return view('vacations.index', compact('stores', 'year', 'availableYears'));
    }

    /**
     * Vista 2: Resumen anual de empleados de una tienda.
     */
    public function storeView(Store $store, int $year)
    {
        $this->authorizeStoreAccess($store->id);
        $employees = $store->employees()->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id')->toArray();

        $periods = EmployeeVacationPeriod::whereIn('employee_id', $employeeIds)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');

        $vacationDaysCounts = EmployeeVacationDay::whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->select('employee_id', DB::raw('COUNT(*) as count'))
            ->groupBy('employee_id')
            ->pluck('count', 'employee_id');

        $employeesData = [];
        foreach ($employees as $employee) {
            $period = $periods->get($employee->id);
            $periodStart = $period?->period_start;
            $periodEnd = $period?->period_end;

            if (!$period) {
                $yearStart = Carbon::createFromDate($year, 1, 1);
                $yearEnd = Carbon::createFromDate($year, 12, 31);
                $periodStart = $employee->start_date && $employee->start_date->gt($yearStart)
                    ? $employee->start_date
                    : $yearStart;
                $periodEnd = $employee->end_date && $employee->end_date->lt($yearEnd)
                    ? $employee->end_date
                    : $yearEnd;
            }

            $daysWorked = $periodStart && $periodEnd
                ? max(0, $periodStart->diffInDays($periodEnd) + 1)
                : 0;
            $vacationGenerated = round($daysWorked * 30 / 365, 2);
            $vacationTaken = (int) ($vacationDaysCounts[$employee->id] ?? 0);
            $vacationRemaining = max(0, $vacationGenerated - $vacationTaken);

            $employeesData[] = (object) [
                'employee' => $employee,
                'period' => $period,
                'period_start' => $periodStart?->format('Y-m-d'),
                'period_end' => $periodEnd?->format('Y-m-d'),
                'days_worked' => $daysWorked,
                'vacation_generated' => $vacationGenerated,
                'vacation_taken' => $vacationTaken,
                'vacation_remaining' => $vacationRemaining,
            ];
        }

        return view('vacations.store', compact('store', 'year', 'employeesData'));
    }

    /**
     * Vista 3: Lista de meses del año.
     */
    public function calendarMonths(Store $store, int $year)
    {
        $this->authorizeStoreAccess($store->id);
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = (object) [
                'month' => $m,
                'monthName' => Carbon::createFromDate($year, $m, 1)->locale('es')->monthName,
            ];
        }
        return view('vacations.calendar-months', compact('store', 'year', 'months'));
    }

    /**
     * Vista 4: Calendario mensual (empleados x semanas x días).
     */
    public function calendarMonth(Store $store, int $year, int $month)
    {
        $this->authorizeStoreAccess($store->id);
        $employees = $store->employees()->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id')->toArray();

        $start = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $start->daysInMonth;

        $vacationDays = EmployeeVacationDay::whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->mapWithKeys(fn ($d) => [$d->employee_id . '_' . $d->date->format('Y-m-d') => true]);

        $vacationDaysByEmployee = EmployeeVacationDay::whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->select('employee_id', DB::raw('COUNT(*) as count'))
            ->groupBy('employee_id')
            ->pluck('count', 'employee_id');

        $weeks = $this->buildWeeksForMonth($year, $month);
        $monthName = $start->locale('es')->monthName;

        return view('vacations.calendar-month', compact(
            'store', 'year', 'month', 'monthName', 'employees',
            'weeks', 'vacationDays', 'vacationDaysByEmployee'
        ));
    }

    /**
     * Guardar periodos (fecha inicio / fecha fin).
     */
    public function updatePeriods(Request $request, Store $store, int $year)
    {
        $this->authorizeStoreAccess($store->id);
        $employeeIds = $store->employees()->pluck('employees.id')->toArray();

        $periods = $request->input('periods', []);
        $companyId = session('company_id');

        foreach ($periods as $empId => $data) {
            $empId = (int) $empId;
            if (!in_array($empId, $employeeIds)) {
                continue;
            }
            $periodStart = $data['period_start'] ?? null;
            $periodEnd = !empty($data['period_end']) ? $data['period_end'] : null;

            if (!$periodStart) {
                continue;
            }

            EmployeeVacationPeriod::updateOrCreate(
                ['employee_id' => $empId, 'year' => $year],
                [
                    'company_id' => $companyId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]
            );
        }

        return redirect()->route('vacations.store', ['store' => $store, 'year' => $year])
            ->with('success', 'Periodos guardados correctamente.');
    }

    /**
     * Toggle día de vacaciones (AJAX).
     */
    public function toggleDay(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $store = $employee->stores()->first();
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Empleado sin tienda asignada'], 403);
        }
        $this->authorizeStoreAccess($store->id);

        $dateNormalized = Carbon::parse($validated['date'])->format('Y-m-d');
        $existing = EmployeeVacationDay::where('employee_id', $validated['employee_id'])
            ->whereDate('date', $dateNormalized)
            ->first();

        $companyId = session('company_id');

        if ($existing) {
            $existing->delete();
            $isVacation = false;
        } else {
            EmployeeVacationDay::create([
                'company_id' => $companyId,
                'employee_id' => $validated['employee_id'],
                'date' => $dateNormalized,
            ]);
            $isVacation = true;
        }

        $year = (int) Carbon::parse($validated['date'])->format('Y');
        $count = EmployeeVacationDay::where('employee_id', $validated['employee_id'])
            ->whereYear('date', $year)
            ->count();

        return response()->json([
            'success' => true,
            'is_vacation' => $isVacation,
            'vacation_days_count' => $count,
        ]);
    }

    /**
     * Registrar vacaciones por semanas.
     */
    public function registerWeeks(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $weeksData = $this->buildWeeksForMonth($year, $month);
        $maxWeek = count($weeksData);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'store_id' => 'required|exists:stores,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'weeks' => 'required|array',
            'weeks.*' => 'integer|min:1|max:' . max(6, $maxWeek),
        ]);

        $store = Store::findOrFail($validated['store_id']);
        $this->authorizeStoreAccess($store->id);

        $employee = Employee::findOrFail($validated['employee_id']);
        if (!$store->employees()->where('employees.id', $employee->id)->exists()) {
            return redirect()->back()->with('error', 'El empleado no pertenece a esta tienda.');
        }

        $companyId = session('company_id');
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $selectedWeeks = array_unique(array_map('intval', $validated['weeks']));

        $added = 0;
        foreach ($weeksData as $week) {
            if (!in_array($week->num, $selectedWeeks)) {
                continue;
            }
            foreach ($week->days as $dayCell) {
                $exists = EmployeeVacationDay::where('employee_id', $employee->id)
                    ->where('date', $dayCell->date)
                    ->exists();
                if (!$exists) {
                    EmployeeVacationDay::create([
                        'company_id' => $companyId,
                        'employee_id' => $employee->id,
                        'date' => $dayCell->date,
                    ]);
                    $added++;
                }
            }
        }

        return redirect()->route('vacations.calendar-month', [
            'store' => $store,
            'year' => $year,
            'month' => $month,
        ])->with('success', "Se registraron {$added} días de vacaciones.");
    }

    /**
     * Genera semanas de lunes a domingo para un mes.
     * Primera semana: primer lunes del mes.
     * Última semana: último domingo necesario para cubrir el mes.
     */
    private function buildWeeksForMonth(int $year, int $month): array
    {
        $first = Carbon::createFromDate($year, $month, 1);
        $last = $first->copy()->endOfMonth();

        // Primer lunes que cae dentro del mes (o el lunes de la semana que contiene el día 1)
        $firstMonday = $first->copy()->startOfWeek(Carbon::MONDAY);
        if ($firstMonday->month != $month) {
            $firstMonday->addWeek();
        }

        // Último domingo necesario para cubrir el mes
        $lastSunday = $last->copy()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $num = 1;
        $current = $firstMonday->copy();

        while ($current->lte($lastSunday)) {
            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $date = $current->copy()->addDays($d);
                $days[] = (object) [
                    'day' => $date->day,
                    'date' => $date->format('Y-m-d'),
                    'weekday' => $date->dayOfWeekIso,
                    'weekdayName' => $date->locale('es')->minDayName,
                ];
            }
            $weeks[] = (object) ['num' => $num, 'days' => $days];
            $num++;
            $current->addDays(7);
        }

        return $weeks;
    }
}
