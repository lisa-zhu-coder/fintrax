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
        $byStore = $cierreRecords->groupBy('store_id');
        $storeTotals = [];
        foreach ($stores as $store) {
            $records = $byStore->get($store->id, collect());
            $storeTotals[$store->id] = [
                'sold' => $records->sum('sold_quantity'),
                'tara' => $records->sum('tara_quantity'),
                'discrepancy' => $records->sum(fn (RingInventory $r) => $r->discrepancy),
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
        $byMonth = $cierreRecords->groupBy(fn (RingInventory $r) => $r->date->month);
        $monthsData = [];
        foreach (range(1, 12) as $month) {
            $records = $byMonth->get($month, collect());
            if ($records->isEmpty()) {
                continue;
            }
            $monthsData[] = (object) [
                'month' => $month,
                'monthName' => Carbon::createFromDate($year, $month, 1)->locale('es')->monthName,
                'year' => $year,
                'sold' => $records->sum('sold_quantity'),
                'tara' => $records->sum('tara_quantity'),
                'discrepancy' => $records->sum(fn (RingInventory $r) => $r->discrepancy),
            ];
        }
        return view('inventory.ring-inventories.store-months', compact('store', 'year', 'monthsData'));
    }

    /**
     * Registros de un mes concreto + resumen (solo turno cierre).
     */
    public function monthRecords(Store $store, int $year, int $month)
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $records = RingInventory::where('store_id', $store->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('id')
            ->get();
        $cierreRecords = $records->where('shift', 'cierre');
        $totalSold = $cierreRecords->sum('sold_quantity');
        $totalTara = $cierreRecords->sum('tara_quantity');
        $totalDiscrepancy = $cierreRecords->sum(fn (RingInventory $r) => $r->discrepancy);
        $monthName = $start->locale('es')->monthName;
        return view('inventory.ring-inventories.month', compact(
            'store', 'year', 'month', 'monthName', 'records',
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'date' => 'required|date',
            'shift' => 'required|in:cambio_turno,cierre',
            'initial_quantity' => 'nullable|integer|min:0',
            'tara_quantity' => 'nullable|integer|min:0',
            'sold_quantity' => 'nullable|integer|min:0',
            'final_quantity' => 'nullable|integer|min:0',
        ]);
        $validated['store_id'] = $this->enforcedStoreIdForCreate((int) ($validated['store_id'] ?? 0)) ?: (int) $validated['store_id'];
        if (!$validated['store_id']) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Debe seleccionar una tienda.']);
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
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'date' => 'required|date',
            'shift' => 'required|in:cambio_turno,cierre',
            'initial_quantity' => 'nullable|integer|min:0',
            'tara_quantity' => 'nullable|integer|min:0',
            'sold_quantity' => 'nullable|integer|min:0',
            'final_quantity' => 'nullable|integer|min:0',
        ]);
        $validated['store_id'] = $this->enforcedStoreIdForCreate((int) ($validated['store_id'] ?? 0) ?: null) ?? (int) $validated['store_id'];
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
}
