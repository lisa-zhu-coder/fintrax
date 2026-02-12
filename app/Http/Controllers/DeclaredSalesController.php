<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\DeclaredSale;
use App\Models\FinancialEntry;
use App\Models\Store;
use App\Models\StoreCashReduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeclaredSalesController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:declared_sales.main.view')->only(['index']);
        $this->middleware('permission:declared_sales.main.create')->only(['generateFromDailyCloses']);
    }

    /**
     * Mostrar lista de ventas declaradas con filtros por tienda y mes
     * Generar resumen mensual
     */
    public function index(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = DeclaredSale::with('store');
        $this->scopeStoreForCurrentUser($query);

        $storeParam = $request->get('store', 'all');
        if ($storeParam !== 'all' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())) {
            $query->where('store_id', $storeParam);
        }

        // Filtro por mes
        if ($request->has('month') && $request->month) {
            try {
                $month = Carbon::parse($request->month)->startOfMonth();
                $query->whereBetween('date', [
                    $month->startOfMonth(),
                    $month->copy()->endOfMonth()
                ]);
            } catch (\Exception $e) {
                // Si el formato de fecha es inválido, ignorar el filtro
            }
        } elseif ($request->has('year') && $request->year) {
            // Filtro por año completo
            try {
                $year = (int) $request->year;
                $query->whereYear('date', $year);
            } catch (\Exception $e) {
                // Si el año es inválido, ignorar el filtro
            }
        } else {
            // Por defecto, mostrar el mes actual
            $query->whereBetween('date', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ]);
        }

        $declaredSales = $query->orderBy('date', 'desc')->orderBy('store_id')->get();
        $stores = $this->getAvailableStores();

        // Generar resumen mensual
        $monthlySummary = $this->generateMonthlySummary($request);

        return view('declared-sales.index', compact('declaredSales', 'stores', 'monthlySummary'));
    }

    /**
     * Generar ventas declaradas desde cierres diarios
     * Aplicar reducción de efectivo por tienda
     * Calcular totales con y sin IVA
     * Crear o actualizar registros de declared_sales
     */
    public function generateFromDailyCloses(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $month = Carbon::parse($validated['month'])->startOfMonth();
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        // Obtener cierres diarios (solo tiendas permitidas para el usuario)
        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        $storeIdParam = isset($validated['store_id']) ? (int) $validated['store_id'] : null;
        if ($enforcedStoreId !== null) {
            $storeIdParam = $enforcedStoreId;
        }
        $query = FinancialEntry::where('type', 'daily_close')
            ->whereBetween('date', [$startDate, $endDate]);
        if ($storeIdParam !== null) {
            $query->where('store_id', $storeIdParam);
        }

        $dailyCloses = $query->with('store')->get();

        if ($dailyCloses->isEmpty()) {
            return redirect()->back()->with('error', 'No se encontraron cierres diarios para el período seleccionado.');
        }

        // Obtener reducciones de efectivo por tienda (solo de la empresa actual)
        $cashReductions = StoreCashReduction::forCurrentCompany()->get()->keyBy('store_id');

        // Agrupar por tienda y mes para crear/actualizar registros
        $groupedByStore = $dailyCloses->groupBy('store_id');

        DB::beginTransaction();
        try {
            foreach ($groupedByStore as $storeId => $closes) {
                // Obtener reducción de efectivo para esta tienda
                $cashReduction = $cashReductions->get($storeId);
                $cashReductionPercent = $cashReduction ? (float) $cashReduction->cash_reduction_percent : 0;

                // Calcular totales
                $bankAmount = 0;
                $cashAmount = 0;

                foreach ($closes as $close) {
                    // bank_amount: suma de ingresos bancarios del cierre diario (TPV)
                    $bankAmount += (float) ($close->tpv ?? 0);

                    // cash_amount: suma de efectivo del cierre diario (SIN reducción aún)
                    $cashAmount += $close->calculateComputedCashSales();
                }

                // Aplicar reducción de efectivo
                // efectivo_reducido = cash_amount * (1 - cash_reduction_percent / 100)
                $efectivoReducido = $cashAmount * (1 - ($cashReductionPercent / 100));

                // Calcular totales con y sin IVA
                // total_with_vat = bank_amount + efectivo_reducido
                $totalWithVat = $bankAmount + $efectivoReducido;

                // total_without_vat = total_with_vat / 1.21
                $totalWithoutVat = $totalWithVat / 1.21;

                // Usar el primer día del mes como fecha representativa
                $representativeDate = $startDate->copy();

                // Buscar si ya existe un registro para este mes y tienda
                $declaredSale = DeclaredSale::where('store_id', $storeId)
                    ->whereYear('date', $representativeDate->year)
                    ->whereMonth('date', $representativeDate->month)
                    ->first();

                if ($declaredSale) {
                    // Actualizar registro existente
                    $declaredSale->update([
                        'date' => $representativeDate,
                        'bank_amount' => round($bankAmount, 2),
                        'cash_amount' => round($cashAmount, 2),
                        'cash_reduction_percent' => $cashReductionPercent,
                        'total_with_vat' => round($totalWithVat, 2),
                        'total_without_vat' => round($totalWithoutVat, 2),
                    ]);
                } else {
                    // Crear nuevo registro
                    DeclaredSale::create([
                        'date' => $representativeDate,
                        'store_id' => $storeId,
                        'bank_amount' => round($bankAmount, 2),
                        'cash_amount' => round($cashAmount, 2),
                        'cash_reduction_percent' => $cashReductionPercent,
                        'total_with_vat' => round($totalWithVat, 2),
                        'total_without_vat' => round($totalWithoutVat, 2),
                    ]);
                }
            }

            DB::commit();

            $message = 'Ventas declaradas generadas correctamente para ' . $month->format('F Y');
            return redirect()->route('declared-sales.index', ['month' => $validated['month']])
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al generar ventas declaradas: ' . $e->getMessage());
        }
    }

    /**
     * Tiendas disponibles para el usuario actual (para selects, listados).
     */
    protected function getAvailableStores()
    {
        return $this->storesForCurrentUser();
    }

    /**
     * Generar resumen mensual agrupado por tienda
     */
    private function generateMonthlySummary(Request $request)
    {
        $query = DeclaredSale::with('store');

        // Aplicar mismos filtros que en index
        if (auth()->user()->store_id) {
            $query->where('store_id', auth()->user()->store_id);
        } elseif ($request->has('store') && $request->store !== 'all') {
            $query->where('store_id', $request->store);
        }

        if ($request->has('month') && $request->month) {
            try {
                $month = Carbon::parse($request->month)->startOfMonth();
                $query->whereBetween('date', [
                    $month->startOfMonth(),
                    $month->copy()->endOfMonth()
                ]);
            } catch (\Exception $e) {
            }
        } elseif ($request->has('year') && $request->year) {
            try {
                $year = (int) $request->year;
                $query->whereYear('date', $year);
            } catch (\Exception $e) {
            }
        } else {
            $query->whereBetween('date', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ]);
        }

        $sales = $query->get();

        // Agrupar por tienda y calcular totales
        $summary = [];
        foreach ($sales as $sale) {
            $storeId = $sale->store_id;
            $storeName = $sale->store ? $sale->store->name : 'Sin tienda';

            if (!isset($summary[$storeId])) {
                $summary[$storeId] = [
                    'store_id' => $storeId,
                    'store_name' => $storeName,
                    'total_bank_amount' => 0,
                    'total_cash_amount' => 0,
                    'total_with_vat' => 0,
                    'total_without_vat' => 0,
                    'count' => 0,
                ];
            }

            $summary[$storeId]['total_bank_amount'] += (float) $sale->bank_amount;
            $summary[$storeId]['total_cash_amount'] += (float) $sale->cash_amount;
            $summary[$storeId]['total_with_vat'] += (float) $sale->total_with_vat;
            $summary[$storeId]['total_without_vat'] += (float) $sale->total_without_vat;
            $summary[$storeId]['count']++;
        }

        // Redondear totales
        foreach ($summary as &$item) {
            $item['total_bank_amount'] = round($item['total_bank_amount'], 2);
            $item['total_cash_amount'] = round($item['total_cash_amount'], 2);
            $item['total_with_vat'] = round($item['total_with_vat'], 2);
            $item['total_without_vat'] = round($item['total_without_vat'], 2);
        }

        return array_values($summary);
    }
}
