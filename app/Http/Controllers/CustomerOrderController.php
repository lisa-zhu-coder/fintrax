<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\CustomerOrder;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:clients.orders.view')->only(['index', 'storeIndex']);
        $this->middleware('permission:clients.orders.create')->only(['create', 'store']);
        $this->middleware('permission:clients.orders.edit')->only(['edit', 'update', 'updateStatus']);
        $this->middleware('permission:clients.orders.delete')->only('destroy');
    }

    /**
     * Listado de tiendas (primera pantalla de Pedidos clientes).
     */
    public function index(): View
    {
        $this->syncStoresFromBusinesses();
        $stores = $this->storesForCurrentUser();
        return view('clients.orders.index', compact('stores'));
    }

    /**
     * Tabla de pedidos de clientes de una tienda.
     */
    public function storeIndex(Request $request, Store $store): View
    {
        $this->authorizeStoreAccess($store->id);

        $query = CustomerOrder::where('store_id', $store->id)->with('creator')->orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $orders = $query->paginate(20)->withQueryString();

        $totalPending = CustomerOrder::where('store_id', $store->id)
            ->whereIn('status', [CustomerOrder::STATUS_PENDING, CustomerOrder::STATUS_ORDERED, CustomerOrder::STATUS_RECEIVED, CustomerOrder::STATUS_NOTIFIED])
            ->count();
        $totalCompleted = CustomerOrder::where('store_id', $store->id)->where('status', CustomerOrder::STATUS_COMPLETED)->count();

        return view('clients.orders.store', compact('store', 'orders', 'totalPending', 'totalCompleted'));
    }

    public function create(Store $store): View
    {
        $this->authorizeStoreAccess($store->id);
        $order = null;
        return view('clients.orders.create', compact('store', 'order'));
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
            'status' => 'required|in:' . implode(',', array_keys(CustomerOrder::statuses())),
            'notification_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['store_id'] = $store->id;
        $validated['company_id'] = $store->company_id;
        $validated['created_by'] = auth()->id();
        $validated['notification_date'] = $request->filled('notification_date') ? $validated['notification_date'] : null;

        CustomerOrder::create($validated);

        return redirect()->route('clients.orders.store', $store)->with('success', 'Pedido de cliente creado correctamente.');
    }

    public function edit(Store $store, CustomerOrder $customer_order): View|RedirectResponse
    {
        $this->authorizeStoreAccess($store->id);
        if ($customer_order->store_id != $store->id) {
            abort(404);
        }
        $order = $customer_order;
        return view('clients.orders.edit', compact('store', 'order'));
    }

    public function update(Request $request, Store $store, CustomerOrder $customer_order): RedirectResponse
    {
        $this->authorizeStoreAccess($store->id);
        if ($customer_order->store_id != $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'client_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'article' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'status' => 'required|in:' . implode(',', array_keys(CustomerOrder::statuses())),
            'notification_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $validated['notification_date'] = $request->filled('notification_date') ? $validated['notification_date'] : null;

        $customer_order->update($validated);

        return redirect()->route('clients.orders.store', $store)->with('success', 'Pedido de cliente actualizado correctamente.');
    }

    public function destroy(Store $store, CustomerOrder $customer_order): RedirectResponse
    {
        $this->authorizeStoreAccess($store->id);
        if ($customer_order->store_id != $store->id) {
            abort(404);
        }
        $customer_order->delete();
        return redirect()->route('clients.orders.store', $store)->with('success', 'Pedido de cliente eliminado correctamente.');
    }

    public function updateStatus(Request $request, CustomerOrder $customer_order): JsonResponse
    {
        $this->authorizeStoreAccess($customer_order->store_id);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(CustomerOrder::statuses())),
        ]);

        $customer_order->update(['status' => $validated['status']]);

        $statuses = CustomerOrder::statuses();
        return response()->json([
            'status' => $customer_order->status,
            'label' => $statuses[$customer_order->status] ?? $customer_order->status,
            'badge_class' => CustomerOrder::statusBadgeClass($customer_order->status),
        ]);
    }
}
