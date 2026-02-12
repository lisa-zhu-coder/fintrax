<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\Store;
use App\Models\StoreCashReduction;
use Illuminate\Http\Request;

class StoreCashReductionController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:settings.cash_reduction.view')->only(['index']);
        $this->middleware('permission:settings.cash_reduction.edit')->only(['update']);
    }

    /**
     * Listar tiendas y mostrar porcentaje de reducción por tienda
     */
    public function index()
    {
        $this->syncStoresFromBusinesses();

        $stores = Store::orderBy('name')->get();
        
        // Obtener reducciones existentes (solo tiendas accesibles)
        $storeIds = $stores->pluck('id')->all();
        $cashReductions = StoreCashReduction::whereIn('store_id', $storeIds)->get()->keyBy('store_id');

        // Preparar datos para la vista
        $storesWithReduction = $stores->map(function ($store) use ($cashReductions) {
            $reduction = $cashReductions->get($store->id);
            return [
                'id' => $store->id,
                'name' => $store->name,
                'cash_reduction_percent' => $reduction ? (float) $reduction->cash_reduction_percent : 0,
                'has_reduction' => $reduction !== null,
            ];
        });

        return view('settings.cash-reductions', compact('storesWithReduction'));
    }

    /**
     * Guardar porcentaje de reducción por tienda
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'reductions' => 'required|array',
            'reductions.*.store_id' => 'required|exists:stores,id',
            'reductions.*.cash_reduction_percent' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $allowedStoreIds = (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                ? null
                : [auth()->user()->getEnforcedStoreId()];
            foreach ($validated['reductions'] as $reductionData) {
                $storeId = (int) $reductionData['store_id'];
                if ($allowedStoreIds !== null && !in_array($storeId, $allowedStoreIds, true)) {
                    continue; // No permitir actualizar tiendas ajenas
                }
                $cashReductionPercent = (float) $reductionData['cash_reduction_percent'];

                // Buscar si ya existe una reducción para esta tienda
                $cashReduction = StoreCashReduction::where('store_id', $storeId)->first();

                if ($cashReductionPercent > 0) {
                    // Si el porcentaje es mayor que 0, crear o actualizar
                    if ($cashReduction) {
                        $cashReduction->update([
                            'cash_reduction_percent' => $cashReductionPercent,
                        ]);
                    } else {
                        StoreCashReduction::create([
                            'store_id' => $storeId,
                            'cash_reduction_percent' => $cashReductionPercent,
                        ]);
                    }
                } else {
                    // Si el porcentaje es 0, eliminar la reducción si existe
                    if ($cashReduction) {
                        $cashReduction->delete();
                    }
                }
            }

            return redirect()->route('store-cash-reductions.index')
                ->with('success', 'Reducciones de efectivo actualizadas correctamente.');

        } catch (\Exception $e) {
            return redirect()->route('store-cash-reductions.index')
                ->with('error', 'Error al actualizar las reducciones: ' . $e->getMessage());
        }
    }
}
