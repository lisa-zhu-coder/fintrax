<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\CustomerRepair;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerRepairController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:clients.repairs.view')->only(['index', 'storeIndex']);
        $this->middleware('permission:clients.repairs.create')->only(['create', 'store']);
        $this->middleware('permission:clients.repairs.edit')->only(['edit', 'update', 'updateStatus']);
        $this->middleware('permission:clients.repairs.delete')->only('destroy');
    }

    /**
     * Listado de tiendas (primera pantalla de Reparaciones).
     */
    public function index(): View
    {
        $this->syncStoresFromBusinesses();
        $stores = $this->storesForCurrentUser();
        return view('clients.repairs.index', compact('stores'));
    }

    /**
     * Tabla de reparaciones de una tienda.
     */
    public function storeIndex(Request $request, Store $store): View
    {
        $this->authorizeStoreAccess($store->id);

        $query = CustomerRepair::where('store_id', $store->id)->with('creator')->orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $repairs = $query->paginate(20)->withQueryString();

        $totalPending = CustomerRepair::where('store_id', $store->id)
            ->whereIn('status', [CustomerRepair::STATUS_PENDING, CustomerRepair::STATUS_FIXED, CustomerRepair::STATUS_NOTIFIED])
            ->count();
        $totalCompleted = CustomerRepair::where('store_id', $store->id)->where('status', CustomerRepair::STATUS_COMPLETED)->count();

        return view('clients.repairs.store', compact('store', 'repairs', 'totalPending', 'totalCompleted'));
    }

    public function create(Store $store): View
    {
        $this->authorizeStoreAccess($store->id);
        $repair = null;
        return view('clients.repairs.create', compact('store', 'repair'));
    }

    public function store(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStoreAccess($store->id);

        $validated = $request->validate([
            'date' => 'required|date',
            'client_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'article' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:500',
            'status' => 'required|in:' . implode(',', array_keys(CustomerRepair::statuses())),
            'notification_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['store_id'] = $store->id;
        $validated['company_id'] = $store->company_id;
        $validated['created_by'] = auth()->id();
        $validated['notification_date'] = $request->filled('notification_date') ? $validated['notification_date'] : null;

        CustomerRepair::create($validated);

        return redirect()->route('clients.repairs.store', $store)->with('success', 'Reparación creada correctamente.');
    }

    public function edit(Store $store, CustomerRepair $customer_repair): View|RedirectResponse
    {
        $this->authorizeStoreAccess($store->id);
        if ($customer_repair->store_id != $store->id) {
            abort(404);
        }
        $repair = $customer_repair;
        return view('clients.repairs.edit', compact('store', 'repair'));
    }

    public function update(Request $request, Store $store, CustomerRepair $customer_repair): RedirectResponse
    {
        $this->authorizeStoreAccess($store->id);
        if ($customer_repair->store_id != $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'client_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'article' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:500',
            'status' => 'required|in:' . implode(',', array_keys(CustomerRepair::statuses())),
            'notification_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $validated['notification_date'] = $request->filled('notification_date') ? $validated['notification_date'] : null;

        $customer_repair->update($validated);

        return redirect()->route('clients.repairs.store', $store)->with('success', 'Reparación actualizada correctamente.');
    }

    public function destroy(Store $store, CustomerRepair $customer_repair): RedirectResponse
    {
        $this->authorizeStoreAccess($store->id);
        if ($customer_repair->store_id != $store->id) {
            abort(404);
        }
        $customer_repair->delete();
        return redirect()->route('clients.repairs.store', $store)->with('success', 'Reparación eliminada correctamente.');
    }

    public function updateStatus(Request $request, CustomerRepair $customer_repair): JsonResponse
    {
        $this->authorizeStoreAccess($customer_repair->store_id);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(CustomerRepair::statuses())),
        ]);

        $customer_repair->update(['status' => $validated['status']]);

        $statuses = CustomerRepair::statuses();
        return response()->json([
            'status' => $customer_repair->status,
            'label' => $statuses[$customer_repair->status] ?? $customer_repair->status,
            'badge_class' => CustomerRepair::statusBadgeClass($customer_repair->status),
        ]);
    }
}
