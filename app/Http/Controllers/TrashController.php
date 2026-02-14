<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\FinancialEntry;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrashController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:trash.main.view')->only(['index']);
        $this->middleware('permission:trash.main.edit')->only(['restore']);
        $this->middleware('permission:trash.main.delete')->only(['forceDelete', 'emptyTrash']);
    }

    /**
     * Listar registros eliminados (papelera).
     * Los registros se eliminan automáticamente después de 30 días.
     */
    public function index(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = FinancialEntry::onlyTrashed()->with(['store', 'creator']);

        $this->scopeStoreForCurrentUser($query);
        if ($request->has('store') && $request->store !== '' && $request->store !== 'all') {
            $query->where('store_id', $request->store);
        }
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        $period = $request->get('period', 'last_30');
        $this->applyPeriodFilterForTrash($query, $period, $request);

        $entries = $query->orderBy('deleted_at', 'desc')->get();
        $stores = $this->getAvailableStores();

        return view('trash.index', compact('entries', 'stores', 'period'));
    }

    /**
     * Restaurar un registro eliminado.
     */
    public function restore(int $id)
    {
        try {
            $entry = FinancialEntry::onlyTrashed()->findOrFail($id);
            $this->authorizeStoreAccess($entry->store_id);
            $entry->restore();

            return redirect()->route('trash.index')->with('success', 'Registro restaurado correctamente');
        } catch (\Exception $e) {
            Log::error('Error restaurando registro: ' . $e->getMessage());
            return back()->with('error', 'Error al restaurar el registro');
        }
    }

    /**
     * Eliminar permanentemente un registro.
     */
    public function forceDelete(int $id)
    {
        try {
            $entry = FinancialEntry::onlyTrashed()->findOrFail($id);
            $this->authorizeStoreAccess($entry->store_id);
            $entry->forceDelete();

            return redirect()->route('trash.index')->with('success', 'Registro eliminado permanentemente');
        } catch (\Exception $e) {
            Log::error('Error eliminando permanentemente: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar permanentemente');
        }
    }

    /**
     * Vaciar toda la papelera.
     */
    public function emptyTrash()
    {
        try {
            $query = FinancialEntry::onlyTrashed();
            $this->scopeStoreForCurrentUser($query);
            $query->forceDelete();

            return redirect()->route('trash.index')->with('success', 'Papelera vaciada correctamente');
        } catch (\Exception $e) {
            Log::error('Error vaciando papelera: ' . $e->getMessage());
            return back()->with('error', 'Error al vaciar la papelera');
        }
    }

    protected function getAvailableStores()
    {
        return $this->storesForCurrentUser();
    }

    /**
     * Filtro de período para papelera (usa deleted_at).
     */
    private function applyPeriodFilterForTrash($query, string $period, ?Request $request = null): void
    {
        if ($request && $request->has('date_from') && $request->has('date_to') && $request->date_from && $request->date_to) {
            try {
                $start = \Carbon\Carbon::parse($request->date_from)->startOfDay();
                $end = \Carbon\Carbon::parse($request->date_to)->endOfDay();
                $query->whereBetween('deleted_at', [$start, $end]);
                return;
            } catch (\Exception $e) {
            }
        }

        $end = now()->endOfDay();
        switch ($period) {
            case 'last_7':
                $start = now()->subDays(6)->startOfDay();
                break;
            case 'last_30':
                $start = now()->subDays(29)->startOfDay();
                break;
            case 'last_90':
                $start = now()->subDays(89)->startOfDay();
                break;
            case 'this_month':
                $start = now()->startOfMonth();
                break;
            case 'last_month':
                $start = now()->subMonth()->startOfMonth();
                $end = now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $start = now()->startOfYear();
                break;
            case 'all':
                return;
            default:
                $start = now()->subDays(29)->startOfDay();
        }

        $query->whereBetween('deleted_at', [$start, $end]);
    }
}
