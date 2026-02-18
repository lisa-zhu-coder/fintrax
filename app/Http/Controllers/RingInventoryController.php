<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\RingInventory;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RingInventoryController extends Controller
{
    use EnforcesStoreScope;

    public function __construct()
    {
        $this->middleware('permission:inventory.rings.view')->only(['index', 'storeMonths', 'monthRecords', 'show']);
        $this->middleware('permission:inventory.rings.create')->only(['create', 'store']);
        $this->middleware('permission:inventory.rings.edit')->only(['edit', 'update']);
        $this->middleware('permission:inventory.rings.delete')->only(['destroy']);
    }

    /**
     * Discrepancia efectiva para un registro Cierre (usa inicial calculado si hace falta).
     */
    private function effectiveDiscrepancyForCierre(RingInventory $r, ?RingInventory $cambioTurnoSameDay): int
    {
        $initial = $cambioTurnoSameDay !== null
            ? ($cambioTurnoSameDay->initial_quantity ?? 0) + ($cambioTurnoSameDay->replenishment_quantity ?? 0)
            : ($r->initial_quantity ?? 0);
        $rep = $r->replenishment_quantity ?? 0;
        $tara = $r->tara_quantity ?? 0;
        $sold = $r->sold_quantity ?? 0;
        $final = $r->final_quantity ?? 0;
        return $initial + $rep + $tara + $sold - $final;
    }

    /**
     * Vista 1: Tabla de tiendas con totales del año (solo shift = cierre).
     * Columnas: Tienda, Anillos vendidos, Taras, Discrepancia.
     * Filtro por año.
     */
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $stores = $this->storesForCurrentUser();
        $query = RingInventory::where('shift', 'cierre');
        $this->scopeStoreForCurrentUser($query);
        $cierreRecords = $query
            ->whereYear('date', $year)
            ->get();
        $storeIds = $stores->pluck('id')->toArray();
        $cambioTurnoByStoreDate = RingInventory::where('shift', 'cambio_turno')
            ->whereIn('store_id', $storeIds)
            ->whereYear('date', $year)
            ->get()
            ->groupBy('store_id')
            ->map(fn ($recs) => $recs->keyBy(fn (RingInventory $x) => $x->date->format('Y-m-d')));
        $byStore = $cierreRecords->groupBy('store_id');
        $storeTotals = [];
        foreach ($stores as $store) {
            $records = $byStore->get($store->id, collect());
            $ctByDate = $cambioTurnoByStoreDate->get($store->id);
            $storeTotals[$store->id] = [
                'sold' => $records->sum('sold_quantity'),
                'tara' => $records->sum('tara_quantity'),
                'discrepancy' => $records->sum(fn (RingInventory $r) => $this->effectiveDiscrepancyForCierre(
                    $r,
                    $ctByDate?->get($r->date->format('Y-m-d'))
                )),
            ];
        }
        $availableYears = RingInventory::where('shift', 'cierre')
            ->get()
            ->pluck('date')
            ->map(fn ($d) => (int) $d->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();
        if ($availableYears->isEmpty() || ! $availableYears->contains(now()->year)) {
            $availableYears = $availableYears->prepend(now()->year)->unique()->sortDesc()->values();
        }
        return view('inventory.ring-inventories.index', compact('stores', 'storeTotals', 'year', 'availableYears'));
    }

    /**
     * Vista 2: Tabla de meses de una tienda en un año (solo shift = cierre).
     * Valores = mismos que el cuadro de resumen mensual de cada mes.
     */
    public function storeMonths(Store $store, int $year)
    {
        $this->authorizeStoreAccess($store->id);
        $cierreRecords = RingInventory::where('store_id', $store->id)
            ->where('shift', 'cierre')
            ->whereYear('date', $year)
            ->get();
        $cambioTurnoByDate = RingInventory::where('store_id', $store->id)
            ->where('shift', 'cambio_turno')
            ->whereYear('date', $year)
            ->get()
            ->keyBy(fn (RingInventory $x) => $x->date->format('Y-m-d'));
        $byMonth = $cierreRecords->groupBy(fn (RingInventory $r) => $r->date->month);
        $monthsData = [];
        foreach (range(1, 12) as $month) {
            $records = $byMonth->get($month, collect());
            $monthsData[] = (object) [
                'month' => $month,
                'monthName' => Carbon::createFromDate($year, $month, 1)->locale('es')->monthName,
                'year' => $year,
                'sold' => $records->sum('sold_quantity'),
                'tara' => $records->sum('tara_quantity'),
                'discrepancy' => $records->sum(fn (RingInventory $r) => $this->effectiveDiscrepancyForCierre(
                    $r,
                    $cambioTurnoByDate->get($r->date->format('Y-m-d'))
                )),
            ];
        }
        return view('inventory.ring-inventories.store-months', compact('store', 'year', 'monthsData'));
    }

    /**
     * Tabla del mes: una fila por cada día × 2 turnos (cambio de turno, cierre).
     * Cada fila tiene el registro existente o null si aún no se ha creado.
     */
    public function monthRecords(Store $store, int $year, int $month)
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $lastDay = (int) $end->format('d');
        $records = RingInventory::where('store_id', $store->id)
            ->whereBetween('date', [$start, $end])
            ->get();
        $byKey = $records->keyBy(fn (RingInventory $r) => $r->date->format('Y-m-d') . '-' . $r->shift);
        $lastCierreBeforeMonth = RingInventory::where('store_id', $store->id)
            ->where('shift', 'cierre')
            ->where('date', '<', $start->format('Y-m-d'))
            ->orderByDesc('date')
            ->first();
        $rows = [];
        $shifts = [
            ['value' => 'cambio_turno', 'label' => 'Cambio de turno'],
            ['value' => 'cierre', 'label' => 'Cierre'],
        ];
        for ($day = 1; $day <= $lastDay; $day++) {
            $date = Carbon::createFromDate($year, $month, $day);
            $dateStr = $date->format('Y-m-d');
            $prevDateStr = $date->copy()->subDay()->format('Y-m-d');
            $cambioTurnoRecord = $byKey->get($dateStr . '-cambio_turno');
            $cierrePrevDay = $prevDateStr >= $start->format('Y-m-d')
                ? $byKey->get($prevDateStr . '-cierre')
                : $lastCierreBeforeMonth;
            foreach ($shifts as $shift) {
                $key = $dateStr . '-' . $shift['value'];
                $record = $byKey->get($key);
                $displayInitial = null;
                if ($shift['value'] === 'cambio_turno') {
                    $displayInitial = $cierrePrevDay?->final_quantity ?? $record?->initial_quantity;
                } else {
                    // Cierre: Inicial = Inicial + Reposición del cambio de turno del mismo día
                    if ($cambioTurnoRecord !== null) {
                        $displayInitial = ($cambioTurnoRecord->initial_quantity ?? 0) + ($cambioTurnoRecord->replenishment_quantity ?? 0);
                    } else {
                        $displayInitial = $record?->initial_quantity;
                    }
                }
                // Discrepancia = inicial + reposición + taras + vendidos - final. Solo si hay dato en Final.
                $displayDiscrepancy = null;
                if ($record !== null && $record->final_quantity !== null) {
                    $init = $displayInitial ?? 0;
                    $rep = $record->replenishment_quantity ?? 0;
                    $tara = $record->tara_quantity ?? 0;
                    $sold = $record->sold_quantity ?? 0;
                    $final = $record->final_quantity;
                    $displayDiscrepancy = $init + $rep + $tara + $sold - $final;
                }
                $rows[] = (object) [
                    'date' => $date,
                    'date_str' => $dateStr,
                    'shift' => $shift['value'],
                    'shift_label' => $shift['label'],
                    'record' => $record,
                    'display_initial' => $displayInitial,
                    'display_discrepancy' => $displayDiscrepancy,
                ];
            }
        }
        $cierreRecords = $records->where('shift', 'cierre');
        $totalSold = $cierreRecords->sum('sold_quantity');
        $totalTara = $cierreRecords->sum('tara_quantity');
        $totalDiscrepancy = collect($rows)->where('shift', 'cierre')->sum(fn ($row) => $row->display_discrepancy ?? 0);
        $monthName = $start->locale('es')->monthName;
        return view('inventory.ring-inventories.month', compact(
            'store', 'year', 'month', 'monthName', 'rows',
            'totalSold', 'totalTara', 'totalDiscrepancy'
        ));
    }

    public function show(RingInventory $ringInventory)
    {
        $this->authorizeStoreAccess($ringInventory->store_id);
        $ringInventory->load('store');
        return view('inventory.ring-inventories.show', compact('ringInventory'));
    }

    public function create(Request $request)
    {
        $stores = $this->storesForCurrentUser();
        $storeId = $request->get('store_id');
        $date = $request->get('date', now()->format('Y-m-d'));
        return view('inventory.ring-inventories.create', compact('stores', 'storeId', 'date'));
    }

    /**
     * Convierte cadenas vacías a null en campos opcionales para que pasen la validación.
     */
    private function normalizeRingInventoryRequest(Request $request): void
    {
        $nullableFields = [
            'initial_quantity', 'replenishment_quantity', 'tara_quantity',
            'sold_quantity', 'final_quantity', 'comment',
        ];
        $merge = [];
        foreach ($nullableFields as $key) {
            if ($request->has($key) && $request->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    public function store(Request $request)
    {
        $this->normalizeRingInventoryRequest($request);
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'date' => 'required|date',
            'shift' => 'required|in:cambio_turno,cierre',
            'initial_quantity' => 'nullable|integer|min:0',
            'replenishment_quantity' => 'nullable|integer',
            'tara_quantity' => 'nullable|integer|min:0',
            'sold_quantity' => 'nullable|integer',
            'final_quantity' => 'nullable|integer|min:0',
            'comment' => 'nullable|string|max:2000',
        ]);
        $validated['store_id'] = $this->enforcedStoreIdForCreate((int) ($validated['store_id'] ?? 0)) ?: (int) $validated['store_id'];
        if (!$validated['store_id']) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Debe seleccionar una tienda.']);
        }
        $canEditInitial = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();
        if (($validated['shift'] ?? '') === 'cierre' && !$canEditInitial) {
            $validated['initial_quantity'] = $this->computedInitialForCierre(
                (int) $validated['store_id'],
                $validated['date']
            );
        }
        if (($validated['shift'] ?? '') === 'cambio_turno' && !$canEditInitial) {
            $validated['initial_quantity'] = $this->initialForCambioTurno(
                (int) $validated['store_id'],
                $validated['date']
            );
        }
        $record = RingInventory::create($validated);
        $date = Carbon::parse($record->date);
        return redirect()->route('ring-inventories.month', [
            'store' => $record->store_id,
            'year' => $date->year,
            'month' => $date->month,
        ])->with('success', 'Registro de inventario creado correctamente.');
    }

    public function edit(RingInventory $ringInventory)
    {
        $ringInventory->load('store');
        $stores = Store::orderBy('name')->get();
        return view('inventory.ring-inventories.edit', compact('ringInventory', 'stores'));
    }

    public function update(Request $request, RingInventory $ringInventory)
    {
        $this->normalizeRingInventoryRequest($request);
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'date' => 'required|date',
            'shift' => 'required|in:cambio_turno,cierre',
            'initial_quantity' => 'nullable|integer|min:0',
            'replenishment_quantity' => 'nullable|integer',
            'tara_quantity' => 'nullable|integer|min:0',
            'sold_quantity' => 'nullable|integer',
            'final_quantity' => 'nullable|integer|min:0',
            'comment' => 'nullable|string|max:2000',
        ]);
        $validated['store_id'] = $this->enforcedStoreIdForCreate((int) ($validated['store_id'] ?? 0) ?: null) ?? (int) $validated['store_id'];
        $canEditInitial = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();
        if (($validated['shift'] ?? '') === 'cierre' && !$canEditInitial) {
            $validated['initial_quantity'] = $this->computedInitialForCierre(
                (int) $validated['store_id'],
                $validated['date']
            );
        }
        if (($validated['shift'] ?? '') === 'cambio_turno' && !$canEditInitial) {
            $validated['initial_quantity'] = $this->initialForCambioTurno(
                (int) $validated['store_id'],
                $validated['date']
            );
        }
        $ringInventory->update($validated);
        $date = Carbon::parse($ringInventory->date);
        return redirect()->route('ring-inventories.month', [
            'store' => $ringInventory->store_id,
            'year' => $date->year,
            'month' => $date->month,
        ])->with('success', 'Registro de inventario actualizado correctamente.');
    }

    public function destroy(RingInventory $ringInventory)
    {
        $this->authorizeStoreAccess($ringInventory->store_id);
        $storeId = $ringInventory->store_id;
        $date = Carbon::parse($ringInventory->date);
        $ringInventory->delete();
        return redirect()->route('ring-inventories.month', [
            'store' => $storeId,
            'year' => $date->year,
            'month' => $date->month,
        ])->with('success', 'Registro de inventario eliminado.');
    }

    /**
     * Inicial del turno cierre = Inicial + Reposición del cambio de turno del mismo día.
     */
    private function computedInitialForCierre(int $storeId, string $date): ?int
    {
        $ct = RingInventory::where('store_id', $storeId)
            ->where('date', $date)
            ->where('shift', 'cambio_turno')
            ->first();
        if ($ct === null) {
            return null;
        }
        $initial = $ct->initial_quantity ?? 0;
        $replenishment = $ct->replenishment_quantity ?? 0;
        return $initial + $replenishment;
    }

    /**
     * Inicial del turno cambio de turno = Final del turno cierre del día anterior.
     * Si no hay datos del día anterior (ej. cambio de mes), usa el final del último cierre con datos.
     */
    private function initialForCambioTurno(int $storeId, string $date): ?int
    {
        $lastCierre = RingInventory::where('store_id', $storeId)
            ->where('shift', 'cierre')
            ->where('date', '<', $date)
            ->orderByDesc('date')
            ->first();
        if ($lastCierre === null) {
            return null;
        }
        return $lastCierre->final_quantity;
    }
}
