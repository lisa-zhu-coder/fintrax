<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\OvertimeSetting;
use App\Models\OvertimeType;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    use EnforcesStoreScope;

    public function __construct()
    {
        $this->middleware('permission:hr.overtime.create')->only(['create', 'store']);
        $this->middleware('permission:hr.overtime.edit')->only(['editRecord', 'updateRecord']);
        $this->middleware('permission:hr.overtime.delete')->only(['destroyRecord']);
    }

    /**
     * Vista 1: Tiendas (con año opcional).
     */
    public function index(Request $request)
    {
        $this->authorizeOvertimeView();
        $year = (int) $request->get('year', now()->year);
        $stores = $this->storesForOvertime();
        $availableYears = $this->availableYears();
        return view('overtime.index', compact('stores', 'year', 'availableYears'));
    }

    /**
     * Vista 2: Meses de una tienda (12 meses con resumen).
     */
    public function storeMonths(Store $store, int $year)
    {
        $this->authorizeOvertimeView();
        $this->authorizeOvertimeStore($store->id);
        $employeeIds = $store->employees()->pluck('employees.id');
        $records = OvertimeRecord::with('overtimeType')->whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->get();
        $types = OvertimeType::orderBy('sort_order')->get();
        $monthsData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthRecords = $records->filter(fn ($r) => (int) $r->date->format('n') === $month);
            $byType = [];
            $totalAmount = 0;
            foreach ($types as $type) {
                $tr = $monthRecords->where('overtime_type_id', $type->id);
                $totalHours = $tr->sum('hours');
                $amount = 0;
                foreach ($tr->groupBy('employee_id') as $empId => $empRecords) {
                    $price = OvertimeSetting::getPriceForEmployeeAndType($empId, $type->id);
                    $amount += $empRecords->sum('hours') * $price;
                }
                $byType[$type->id] = (object) ['hours' => $totalHours, 'amount' => $amount];
                $totalAmount += $amount;
            }
            $monthsData[] = (object) [
                'month' => $month,
                'monthName' => Carbon::createFromDate($year, $month, 1)->locale('es')->monthName,
                'year' => $year,
                'byType' => $byType,
                'types' => $types,
                'totalAmount' => $totalAmount,
            ];
        }
        return view('overtime.store-months', compact('store', 'year', 'monthsData', 'types'));
    }

    /**
     * Vista 3: Empleadas del mes (tabla por empleada con totales por tipo).
     */
    public function monthDetail(Store $store, int $year, int $month)
    {
        $this->authorizeOvertimeView();
        $this->authorizeOvertimeStore($store->id);
        $monthStr = sprintf('%04d-%02d', $year, $month);
        $start = Carbon::parse($year . '-' . $month . '-01');
        $end = $start->copy()->endOfMonth();
        $employeeIds = $store->employees()->pluck('employees.id');
        $records = OvertimeRecord::with('overtimeType')->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();
        $types = OvertimeType::orderBy('sort_order')->get();
        $byEmployee = $records->groupBy('employee_id');
        $employeesData = [];
        foreach ($byEmployee as $empId => $empRecords) {
            $employee = Employee::find($empId);
            if (! $employee) {
                continue;
            }
            $byType = [];
            $totalAmount = 0;
            foreach ($types as $type) {
                $tr = $empRecords->where('overtime_type_id', $type->id);
                $hours = $tr->sum('hours');
                $price = OvertimeSetting::getPriceForEmployeeAndType($empId, $type->id);
                $amount = $hours * $price;
                $byType[$type->id] = (object) ['hours' => $hours, 'price' => $price, 'amount' => $amount];
                $totalAmount += $amount;
            }
            $employeesData[] = (object) [
                'employee' => $employee,
                'byType' => $byType,
                'types' => $types,
                'totalAmount' => $totalAmount,
            ];
        }
        usort($employeesData, fn ($a, $b) => strcmp($a->employee->full_name ?? '', $b->employee->full_name ?? ''));
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('es')->monthName;
        $employees = $store->employees()->orderBy('full_name')->get();
        return view('overtime.month', compact('store', 'year', 'month', 'monthName', 'employeesData', 'employees', 'types'));
    }

    /**
     * Formulario añadir horas.
     */
    public function create(Store $store, int $year, int $month)
    {
        $this->authorizeOvertimeView();
        $this->authorizeOvertimeStore($store->id);
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('es')->monthName;
        $employees = $store->employees()->orderBy('full_name')->get();
        $types = OvertimeType::orderBy('sort_order')->get();
        return view('overtime.create', compact('store', 'year', 'month', 'monthName', 'employees', 'types'));
    }

    /**
     * Guardar nuevo registro de horas (puede crear varios registros si hay varios tipos con horas).
     */
    public function store(Request $request, Store $store, int $year, int $month)
    {
        $this->authorizeOvertimeStore($store->id);
        $types = OvertimeType::orderBy('sort_order')->get();
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            ...$types->mapWithKeys(fn ($t) => ["hours_type_{$t->id}" => 'nullable|numeric|min:0'])->toArray(),
        ], [], [
            'employee_id' => 'empleada',
            'date' => 'fecha',
        ]);
        $employeeId = (int) $validated['employee_id'];
        $belongsToStore = $store->employees()->where('employees.id', $employeeId)->exists();
        if (! $belongsToStore) {
            return redirect()->back()->with('error', 'La empleada no pertenece a esta tienda.');
        }
        $created = 0;
        foreach ($types as $type) {
            $hours = (float) ($validated["hours_type_{$type->id}"] ?? 0);
            if ($hours > 0) {
                OvertimeRecord::create([
                    'employee_id' => $employeeId,
                    'date' => $validated['date'],
                    'overtime_type_id' => $type->id,
                    'hours' => $hours,
                ]);
                $created++;
            }
        }
        $msg = $created > 0 ? 'Horas añadidas correctamente.' : 'No se añadieron horas (debe indicar al menos un valor mayor que 0).';
        return redirect()->route('overtime.month', ['store' => $store, 'year' => $year, 'month' => $month])
            ->with($created > 0 ? 'success' : 'error', $msg);
    }

    /**
     * Historial completo de una empleada.
     */
    public function employeeDetail(Employee $employee)
    {
        $this->authorizeOvertimeView();
        $this->authorizeOvertimeEmployee($employee);
        $records = $employee->overtimeRecords()->with('overtimeType')->orderByDesc('date')->get();
        $types = OvertimeType::orderBy('sort_order')->get();
        $pricesByType = OvertimeSetting::getPricesByTypeForEmployee($employee->id);
        $rows = $records->map(function ($r) use ($pricesByType) {
            $price = $pricesByType[$r->overtime_type_id] ?? 0;
            $amount = (float) $r->hours * $price;
            return (object) [
                'record' => $r,
                'price' => $price,
                'amount' => $amount,
            ];
        });
        return view('overtime.employee', compact('employee', 'rows', 'types'));
    }

    /**
     * Editar registro.
     */
    public function editRecord(OvertimeRecord $overtimeRecord)
    {
        $employee = $overtimeRecord->employee;
        $store = $employee->stores()->first();
        if ($store) {
            $this->authorizeOvertimeStore($store->id);
        }
        $types = OvertimeType::orderBy('sort_order')->get();
        return view('overtime.edit-record', compact('overtimeRecord', 'employee', 'store', 'types'));
    }

    /**
     * Actualizar registro.
     */
    public function updateRecord(Request $request, OvertimeRecord $overtimeRecord)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'overtime_type_id' => 'required|exists:overtime_types,id',
            'hours' => 'required|numeric|min:0',
        ]);
        $overtimeRecord->update([
            'date' => $validated['date'],
            'overtime_type_id' => $validated['overtime_type_id'],
            'hours' => (float) $validated['hours'],
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
            $this->authorizeOvertimeStore($store->id);
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

    /**
     * Tiendas que el usuario puede ver en horas extras (view_own vs view_store).
     */
    private function storesForOvertime()
    {
        $user = Auth::user();
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return Store::all();
        }
        $canViewStore = $user->hasPermission('hr.overtime.view_store');
        $canViewOwn = $user->hasPermission('hr.overtime.view_own');
        if (!$canViewStore && !$canViewOwn) {
            return collect();
        }
        if ($canViewStore) {
            return $this->storesForCurrentUser();
        }
        // view_own: solo tiendas donde el usuario tiene su ficha de empleado
        $employee = $user->employee;
        if (!$employee) {
            return collect();
        }
        return $employee->stores;
    }

    private function authorizeOvertimeStore(?int $storeId): void
    {
        $user = Auth::user();
        if (!$user) abort(403, 'No autenticado.');
        if ($user->isSuperAdmin() || $user->isAdmin()) return;
        $canViewStore = $user->hasPermission('hr.overtime.view_store');
        if ($canViewStore) {
            $this->authorizeStoreAccess($storeId);
            return;
        }
        $canViewOwn = $user->hasPermission('hr.overtime.view_own');
        if ($canViewOwn) {
            $employee = $user->employee;
            if (!$employee) abort(403, 'No tienes ficha de empleado.');
            $belongsToStore = $employee->stores()->where('stores.id', $storeId)->exists();
            if (!$belongsToStore) abort(403, 'Solo puedes ver horas extras de tu tienda.');
            return;
        }
        abort(403, 'No tienes permiso para ver horas extras.');
    }

    private function authorizeOvertimeEmployee(Employee $employee): void
    {
        $user = Auth::user();
        if (!$user) abort(403, 'No autenticado.');
        if ($user->isSuperAdmin() || $user->isAdmin()) return;
        $canViewStore = $user->hasPermission('hr.overtime.view_store');
        if ($canViewStore) {
            $storeIds = $employee->stores->pluck('id')->toArray();
            foreach ($storeIds as $sid) {
                if ($user->canAccessStore($sid)) return;
            }
            abort(403, 'No tienes acceso a esta empleada.');
            return;
        }
        $canViewOwn = $user->hasPermission('hr.overtime.view_own');
        if ($canViewOwn) {
            if ($employee->user_id !== $user->id) abort(403, 'Solo puedes ver tus propias horas extras.');
            return;
        }
        abort(403, 'No tienes permiso para ver horas extras.');
    }

    private function authorizeOvertimeView(): void
    {
        $user = Auth::user();
        if (!$user) abort(403, 'No autenticado.');
        if ($user->isSuperAdmin() || $user->isAdmin()) return;
        if ($user->hasPermission('hr.overtime.view_own') || $user->hasPermission('hr.overtime.view_store')) return;
        abort(403, 'No tienes permiso para ver horas extras.');
    }
}
