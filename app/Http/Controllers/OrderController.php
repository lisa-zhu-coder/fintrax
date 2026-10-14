<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\CashWallet;
use App\Models\CashWalletExpense;
use App\Models\FinancialEntry;
use App\Models\Order;
use App\Models\Store;
use App\Models\Supplier;
use App\Support\OrderTableSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;
    
    public function __construct()
    {
        $this->middleware('permission:orders.main.view')->only(['index', 'show', 'supplierOrders', 'exportSupplierOrders']);
        $this->middleware('permission:orders.main.create')->only(['create', 'store']);
        $this->middleware('permission:orders.main.edit')->only(['edit', 'update']);
        $this->middleware('permission:orders.main.delete')->only('destroy');
    }

    /**
     * Vista principal: listado de proveedores con estadísticas de pedidos (NO pedidos individuales).
     */
    public function index(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $statsQuery = Order::query()->whereNotNull('orders.supplier_id');
        $this->scopeStoreForCurrentUser($statsQuery);
        if ($request->has('period')) {
            $this->applyPeriodFilter($statsQuery, $request->period, $request);
        }

        $statRows = $this->orderStatsGroupedBy($statsQuery, 'supplier_id');
        $suppliers = Supplier::whereIn('id', $statRows->pluck('group_id')->filter())->get()->keyBy('id');

        $suppliersWithStats = $statRows
            ->map(function ($row) use ($suppliers) {
                $supplier = $suppliers->get($row->group_id);
                if (! $supplier) {
                    return null;
                }

                return [
                    'supplier' => $supplier,
                    'total_orders' => (int) $row->total_orders,
                    'total_amount' => (float) $row->total_amount,
                    'total_paid' => (float) $row->total_paid,
                    'total_pending' => (float) $row->total_pending,
                ];
            })
            ->filter()
            ->values()
            ->all();

        [$sortBy, $sortDir] = $this->resolveSortParams($request, [
            'supplier' => 'asc',
            'type' => 'asc',
            'total_orders' => 'desc',
            'total_amount' => 'desc',
            'total_paid' => 'desc',
            'total_pending' => 'desc',
        ], 'supplier', 'asc');

        $suppliersWithStats = $this->sortSuppliersWithStats($suppliersWithStats, $sortBy, $sortDir);

        return view('orders.index', compact('suppliersWithStats'));
    }

    /**
     * Vista de segundo nivel: pedidos de un proveedor concreto.
     * Filtros: tienda (store_id), fecha (date_from, date_to), forma de pago (payment_method).
     */
    public function supplierOrders(Request $request, Supplier $supplier)
    {
        $this->syncStoresFromBusinesses();

        $supplier->load('expenseCategory');

        // Resumen por tienda: agregado en SQL (sin cargar todos los pedidos)
        $querySummary = Order::query()->where('supplier_id', $supplier->id);
        $this->scopeStoreForCurrentUser($querySummary);
        $summaryRows = $this->orderStatsGroupedBy($querySummary, 'store_id');
        $storesById = Store::whereIn('id', $summaryRows->pluck('group_id')->filter())->get()->keyBy('id');
        $summary = [
            'total_orders' => (int) $summaryRows->sum('total_orders'),
            'total_amount' => (float) $summaryRows->sum('total_amount'),
            'total_paid' => (float) $summaryRows->sum('total_paid'),
            'total_pending' => (float) $summaryRows->sum('total_pending'),
        ];
        $summaryByStore = $summaryRows->map(function ($row) use ($storesById) {
            $store = $storesById->get($row->group_id);

            return [
                'store_id' => $row->group_id,
                'store_name' => $store ? $store->name : 'Sin tienda',
                'total_orders' => (int) $row->total_orders,
                'total_amount' => (float) $row->total_amount,
                'total_paid' => (float) $row->total_paid,
                'total_pending' => (float) $row->total_pending,
            ];
        })->all();

        ['orders' => $orders, 'originStoresById' => $originStoresById, 'allowedFilters' => $allowedFilters] = $this->filteredSupplierOrderList($request, $supplier);

        [$storeSortBy, $storeSortDir] = $this->resolveSortParams($request, [
            'store_name' => 'asc',
            'store_total_orders' => 'desc',
            'store_total_amount' => 'desc',
            'store_total_paid' => 'desc',
            'store_total_pending' => 'desc',
        ], 'store_name', 'asc', 'store_sort_by', 'store_sort_dir');

        $summaryByStore = $this->sortSummaryByStore($summaryByStore, $storeSortBy, $storeSortDir);

        $stores = $this->storesForCurrentUser();

        $filters = [
            'store_id' => in_array('store_id', $allowedFilters, true) ? $this->resolveStoreFilterId($request->input('store_id')) : null,
            'date_from' => in_array('period', $allowedFilters, true) ? $request->get('date_from') : null,
            'date_to' => in_array('period', $allowedFilters, true) ? $request->get('date_to') : null,
            'payment_method' => in_array('payment_method', $allowedFilters, true) ? $request->get('payment_method') : null,
            'period' => in_array('period', $allowedFilters, true)
                ? ($request->filled('date_from') && $request->filled('date_to') ? 'custom' : $request->get('period', 'this_year'))
                : null,
            'search' => in_array('search', $allowedFilters, true) ? $request->get('search') : null,
            'status' => in_array('status', $allowedFilters, true) ? $request->get('status') : null,
            'split_type' => in_array('split_type', $allowedFilters, true) ? $request->get('split_type') : null,
            'origin_store_id' => in_array('origin_store_id', $allowedFilters, true) ? $this->resolveStoreFilterId($request->input('origin_store_id')) : null,
            'concept' => in_array('concept', $allowedFilters, true) ? $request->get('concept') : null,
        ];

        return view('orders.supplier', compact('supplier', 'orders', 'summary', 'summaryByStore', 'stores', 'filters', 'originStoresById', 'allowedFilters'));
    }

    public function exportSupplierOrders(Request $request, Supplier $supplier)
    {
        $this->syncStoresFromBusinesses();

        ['orders' => $orders, 'originStoresById' => $originStoresById] = $this->filteredSupplierOrderList($request, $supplier, false);

        $columns = collect(OrderTableSettings::visibleColumns('supplier_orders'))
            ->reject(fn (array $column) => ($column['key'] ?? '') === 'actions')
            ->values();

        $filename = 'pedidos-'.Str::slug($supplier->name).'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($orders, $originStoresById, $columns) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $columns->pluck('label')->all(), ';', '"', '\\');

            foreach ($orders as $order) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $this->supplierOrderExportCell($order, $column['key'], $originStoresById);
                }
                fputcsv($out, $row, ';', '"', '\\');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create()
    {
        $this->syncStoresFromBusinesses();
        $stores = $this->storesForCurrentUser();
        $suppliers = Supplier::orderBy('name')->get();
        $cashWallets = CashWallet::orderBy('name')->get();
        $order = null;
        return view('orders.create', compact('stores', 'suppliers', 'order', 'cashWallets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:255',
            'order_number' => 'required|string|max:255',
            'concept' => 'required|in:pedido,royalty,rectificacion,tara',
            'amount' => 'required|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.id' => 'nullable|integer',
            'payments.*.date' => 'required|date',
            'payments.*.method' => 'required|in:cash,transfer,card',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.cash_source' => 'nullable|in:wallet,store',
            'payments.*.cash_wallet_id' => 'nullable|exists:cash_wallets,id',
            'payments.*.cash_store_id' => 'nullable|exists:stores,id',
            'order_split_stores' => 'nullable|array',
            'order_split_stores.*' => 'exists:stores,id',
            'order_split_amounts' => 'nullable|array',
            'order_split_amounts.*' => 'numeric|min:0',
        ]);
        $validated['store_id'] = $this->enforcedStoreIdForCreate((int) ($validated['store_id'] ?? 0) ?: null);
        if ($validated['store_id'] === null) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Debe seleccionar una tienda.']);
        }
        $this->validateOrderPaymentsCashSource($request->input('payments', []));

        // Manejar división de pedidos entre tiendas (solo admin puede dividir entre varias tiendas)
        $splitStores = $request->has('order_split_stores') && !empty($request->order_split_stores)
            ? array_values(array_intersect($request->order_split_stores, (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()) ? $request->order_split_stores : [$validated['store_id']]))
            : [];
        if (!empty($splitStores)) {
            $splitAmounts = $request->order_split_amounts ?? [];
            $totalAmount = (float) $validated['amount'];
            $payingStoreId = (int) $validated['store_id'];
            
            // Si no hay cantidades específicas, dividir por partes iguales
            if (empty($splitAmounts)) {
                $amountPerStore = $totalAmount / count($splitStores);
                foreach ($splitStores as $storeId) {
                    $splitAmounts[$storeId] = round($amountPerStore, 2);
                }
            }
            
            // Crear un pedido por cada tienda
            $createdOrders = [];
            foreach ($splitStores as $storeId) {
                $storeAmount = (float) ($splitAmounts[$storeId] ?? 0);
                if ($storeAmount > 0) {
                    $orderData = $validated;
                    $orderData['store_id'] = $storeId;
                    $orderData['amount'] = $storeAmount;
                    $orderData['store_split'] = [
                        'stores' => $splitStores,
                        'amounts' => $splitAmounts,
                        'total' => $totalAmount,
                        'origin_store_id' => $payingStoreId,
                    ];
                    
                    $order = Order::create($orderData);

                    $this->syncOrderPayments(
                        $order,
                        $this->proportionalPaymentsInput($request->input('payments', []), $totalAmount, $storeAmount),
                        $payingStoreId
                    );
                    
                    $this->addHistoryEntry($order, 'created');
                    $this->syncExpensesFromOrder($order);
                    
                    $createdOrders[] = $order;
                }
            }
            
            if (count($createdOrders) > 0) {
                return redirect()->route('orders.index')->with('success', 
                    'Pedido dividido entre ' . count($createdOrders) . ' tienda' . (count($createdOrders) > 1 ? 's' : '') . ' creado correctamente.');
            }
        }

        $order = Order::create($validated);

        $this->syncOrderPayments($order, $request->input('payments', []), (int) $order->store_id);

        $this->addHistoryEntry($order, 'created');
        $this->syncExpensesFromOrder($order);

        $redirectTo = $request->has('supplier_id') && $order->supplier_id
            ? route('orders.supplier', $order->supplier)
            : route('orders.index');
        return redirect($redirectTo)->with('success', 'Pedido creado correctamente.');
    }

    public function show(Order $order)
    {
        $this->authorizeStoreAccess($order->store_id);
        $order->load(['store', 'supplier', 'payments']);
        $stores = $this->storesForCurrentUser();
        return view('orders.show', compact('order', 'stores'));
    }

    public function edit(Order $order)
    {
        $this->authorizeStoreAccess($order->store_id);
        $this->syncStoresFromBusinesses();
        $stores = $this->storesForCurrentUser();
        $suppliers = Supplier::orderBy('name')->get();
        $cashWallets = CashWallet::orderBy('name')->get();
        $order->load('payments');
        return view('orders.edit', compact('order', 'stores', 'suppliers', 'cashWallets'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:255',
            'order_number' => 'required|string|max:255',
            'concept' => 'required|in:pedido,royalty,rectificacion,tara',
            'amount' => 'required|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.id' => 'nullable|integer',
            'payments.*.date' => 'required|date',
            'payments.*.method' => 'required|in:cash,transfer,card',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.cash_source' => 'nullable|in:wallet,store',
            'payments.*.cash_wallet_id' => 'nullable|exists:cash_wallets,id',
            'payments.*.cash_store_id' => 'nullable|exists:stores,id',
            'order_split_stores' => 'nullable|array',
            'order_split_stores.*' => 'exists:stores,id',
            'order_split_amounts' => 'nullable|array',
            'order_split_amounts.*' => 'numeric|min:0',
        ]);
        $validated['store_id'] = $this->enforcedStoreIdForCreate((int) ($validated['store_id'] ?? 0) ?: null);
        if ($validated['store_id'] === null) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Debe seleccionar una tienda.']);
        }
        $this->validateOrderPaymentsCashSource($request->input('payments', []));

        $oldData = $order->toArray();

        // Manejar división de pedidos entre tiendas (solo admin puede dividir entre varias tiendas)
        $splitStores = $request->has('order_split_stores') && !empty($request->order_split_stores)
            ? array_values(array_intersect(
                $request->order_split_stores,
                (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()) ? $request->order_split_stores : [$validated['store_id']]
            ))
            : [];
        if (!empty($splitStores)) {
            $splitAmounts = $request->order_split_amounts ?? [];
            $totalAmount = (float) $validated['amount'];
            $payingStoreId = (int) $validated['store_id'];

            if (empty($splitAmounts)) {
                $amountPerStore = $totalAmount / count($splitStores);
                foreach ($splitStores as $storeId) {
                    $splitAmounts[$storeId] = round($amountPerStore, 2);
                }
            }

            $storeSplitMeta = [
                'stores' => $splitStores,
                'amounts' => $splitAmounts,
                'total' => $totalAmount,
                'origin_store_id' => $payingStoreId,
            ];

            $relatedOrders = $this->findSplitRelatedOrders($order)->keyBy('store_id');
            // El pedido que se edita siempre se reutiliza (aunque cambie de tienda)
            $ordersByStore = collect();
            foreach ($relatedOrders as $related) {
                if ((int) $related->id === (int) $order->id) {
                    continue;
                }
                $ordersByStore[(int) $related->store_id] = $related;
            }

            $syncedCount = 0;
            $primaryAssigned = false;

            foreach ($splitStores as $storeId) {
                $storeId = (int) $storeId;
                $storeAmount = (float) ($splitAmounts[$storeId] ?? 0);
                if ($storeAmount <= 0) {
                    continue;
                }

                $orderData = $validated;
                $orderData['store_id'] = $storeId;
                $orderData['amount'] = $storeAmount;
                $orderData['store_split'] = $storeSplitMeta;

                if (! $primaryAssigned) {
                    $targetOrder = $order;
                    $targetOrder->update($orderData);
                    $primaryAssigned = true;
                    $isNew = false;
                } elseif ($ordersByStore->has($storeId)) {
                    $targetOrder = $ordersByStore->get($storeId);
                    $targetOrder->update($orderData);
                    $ordersByStore->forget($storeId);
                    $isNew = false;
                } else {
                    $targetOrder = Order::create($orderData);
                    $isNew = true;
                }

                $basePayments = $request->input('payments', []);
                if ((int) $targetOrder->id === (int) $order->id) {
                    // Pedido editado: conservar ids del formulario y solo escalar importes
                    $paymentsForStore = $this->proportionalPaymentsInput($basePayments, $totalAmount, $storeAmount);
                } else {
                    // Hermanos: no usar ids del formulario; rematchear por índice
                    $basePayments = array_map(function ($p) {
                        unset($p['id']);

                        return $p;
                    }, $basePayments);
                    $existingIds = $isNew
                        ? []
                        : $targetOrder->payments()->orderBy('id')->pluck('id')->values()->all();
                    $paymentsForStore = $this->proportionalPaymentsInput(
                        $basePayments,
                        $totalAmount,
                        $storeAmount,
                        $existingIds
                    );
                }

                $this->syncOrderPayments($targetOrder, $paymentsForStore, $payingStoreId);
                $this->syncExpensesFromOrder($targetOrder);

                if ($isNew) {
                    $this->addHistoryEntry($targetOrder, 'created');
                }
                $syncedCount++;
            }

            // Pedidos hermanos que ya no participan en el split: borrar en duro (efecto de edición)
            foreach ($ordersByStore as $orphan) {
                $this->forceDeleteOrderCascade($orphan);
            }

            $this->addHistoryEntry($order->fresh(), 'updated', $oldData);

            return redirect()->route('orders.index')->with('success',
                'Pedido dividido entre ' . $syncedCount . ' tienda' . ($syncedCount > 1 ? 's' : '') . ' actualizado correctamente.');
        }

        // Si deja de estar dividido, eliminar hermanos del split anterior
        if ($order->store_split) {
            foreach ($this->findSplitRelatedOrders($order) as $related) {
                if ((int) $related->id === (int) $order->id) {
                    continue;
                }
                $this->forceDeleteOrderCascade($related);
            }
            $validated['store_split'] = null;
        }

        $order->update($validated);

        $this->syncOrderPayments($order, $request->input('payments', []), (int) $order->store_id);
        $this->addHistoryEntry($order, 'updated', $oldData);
        $this->syncExpensesFromOrder($order);

        $redirectTo = $order->supplier_id
            ? route('orders.supplier', $order->supplier)
            : route('orders.index');
        return redirect($redirectTo)->with('success', 'Pedido actualizado correctamente.');
    }

    public function destroy(Order $order)
    {
        $supplierId = $order->supplier_id;
        $supplier = $order->supplier;

        // Eliminar CashWalletExpense asociados primero (FinancialEntry usa SoftDeletes, la CASCADE de BD no borra cash_wallet_expenses)
        $entryIds = FinancialEntry::where('type', 'expense')
            ->where('notes', 'like', '%"order_id":' . $order->id . '%')
            ->pluck('id');
        if ($entryIds->isNotEmpty()) {
            CashWalletExpense::whereIn('financial_entry_id', $entryIds)->delete();
        }
        FinancialEntry::where('type', 'expense')
            ->where('notes', 'like', '%"order_id":' . $order->id . '%')
            ->delete();

        // Si es un pedido dividido, eliminar también los pedidos relacionados y sus gastos/cartera
        if ($order->store_split) {
            Order::where('id', '!=', $order->id)
                ->where('invoice_number', $order->invoice_number)
                ->where('order_number', $order->order_number)
                ->where('store_split', '!=', null)
                ->get()
                ->each(function ($relatedOrder) {
                    $relatedEntryIds = FinancialEntry::where('type', 'expense')
                        ->where('notes', 'like', '%"order_id":' . $relatedOrder->id . '%')
                        ->pluck('id');
                    if ($relatedEntryIds->isNotEmpty()) {
                        CashWalletExpense::whereIn('financial_entry_id', $relatedEntryIds)->delete();
                    }
                    FinancialEntry::where('type', 'expense')
                        ->where('notes', 'like', '%"order_id":' . $relatedOrder->id . '%')
                        ->delete();
                    $relatedOrder->delete();
                });
        }

        $this->addHistoryEntry($order, 'deleted');
        $order->delete();

        $redirectTo = $supplierId && $supplier
            ? route('orders.supplier', $supplier)
            : route('orders.index');

        return redirect($redirectTo)->with('success', 'Pedido eliminado correctamente.');
    }

    /**
     * Agrega pedidos (importe, pagado, pendiente) agrupados por una columna.
     * Evita cargar todos los pedidos y disparar un SUM por cada uno.
     *
     * @return \Illuminate\Support\Collection<int, object{group_id: mixed, total_orders: mixed, total_amount: mixed, total_paid: mixed, total_pending: mixed}>
     */
    private function orderStatsGroupedBy($query, string $groupColumn)
    {
        $paidJoin = '(SELECT order_id, COALESCE(SUM(amount), 0) as paid FROM order_payments WHERE deleted_at IS NULL GROUP BY order_id) as order_paid';

        return $query
            ->leftJoin(DB::raw($paidJoin), 'order_paid.order_id', '=', 'orders.id')
            ->selectRaw("orders.{$groupColumn} as group_id")
            ->selectRaw('COUNT(orders.id) as total_orders')
            ->selectRaw('COALESCE(SUM(orders.amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(COALESCE(order_paid.paid, 0)), 0) as total_paid')
            ->selectRaw('COALESCE(SUM(CASE
                WHEN ABS(orders.amount) < 0.005 THEN 0
                WHEN orders.amount >= 0 AND (orders.amount - COALESCE(order_paid.paid, 0)) > 0.009 THEN (orders.amount - COALESCE(order_paid.paid, 0))
                WHEN orders.amount < 0 AND (orders.amount - COALESCE(order_paid.paid, 0)) < -0.009 THEN (orders.amount - COALESCE(order_paid.paid, 0))
                ELSE 0
            END), 0) as total_pending')
            ->groupBy("orders.{$groupColumn}")
            ->get();
    }

    /**
     * @return array{orders: \Illuminate\Support\Collection<int, Order>, originStoresById: \Illuminate\Support\Collection<int, string>, allowedFilters: list<string>}
     */
    private function filteredSupplierOrderList(Request $request, Supplier $supplier, bool $withInvoices = true): array
    {
        $query = Order::query()
            ->select([
                'orders.id',
                'orders.date',
                'orders.store_id',
                'orders.supplier_id',
                'orders.invoice_number',
                'orders.order_number',
                'orders.concept',
                'orders.amount',
                'orders.store_split',
            ])
            ->with(['store', 'payments'])
            ->where('supplier_id', $supplier->id);

        $allowedFilters = OrderTableSettings::supplierOrdersFilterKeys();

        if (in_array('store_id', $allowedFilters, true) && ($storeId = $this->resolveStoreFilterId($request->input('store_id'))) !== null) {
            $this->authorizeStoreAccess($storeId);
            $query->where('store_id', $storeId);
        } else {
            $this->scopeStoreForCurrentUser($query);
        }

        if (in_array('payment_method', $allowedFilters, true) && $request->filled('payment_method') && in_array($request->payment_method, ['cash', 'transfer', 'card'], true)) {
            $query->whereHas('payments', fn ($q) => $q->where('method', $request->payment_method));
        }

        if (in_array('status', $allowedFilters, true) && $request->filled('status') && in_array($request->status, ['pendiente', 'pagado'], true)) {
            $subSelect = 'SELECT COALESCE(SUM(amount), 0) FROM order_payments WHERE order_payments.order_id = orders.id AND order_payments.deleted_at IS NULL';
            if ($request->status === 'pendiente') {
                $query->where(function ($q) use ($subSelect) {
                    $q->where(function ($q2) use ($subSelect) {
                        $q2->where('orders.amount', '>', 0)
                            ->whereRaw("orders.amount > ({$subSelect}) + 0.009");
                    })->orWhere(function ($q2) use ($subSelect) {
                        $q2->where('orders.amount', '<', 0)
                            ->whereRaw("orders.amount < ({$subSelect}) - 0.009");
                    });
                });
            } else {
                $query->where(function ($q) use ($subSelect) {
                    $q->whereRaw('ABS(orders.amount) < 0.009')
                        ->orWhere(function ($q2) use ($subSelect) {
                            $q2->where('orders.amount', '>=', 0)
                                ->whereRaw("({$subSelect}) >= orders.amount - 0.009");
                        })
                        ->orWhere(function ($q2) use ($subSelect) {
                            $q2->where('orders.amount', '<', 0)
                                ->whereRaw("({$subSelect}) <= orders.amount + 0.009");
                        });
                });
            }
        }

        if (in_array('concept', $allowedFilters, true) && $request->filled('concept') && in_array($request->concept, ['pedido', 'royalty', 'rectificacion', 'tara'], true)) {
            $query->where('concept', $request->concept);
        }

        if (in_array('search', $allowedFilters, true) && $request->filled('search')) {
            $term = '%'.trim($request->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', $term)
                    ->orWhere('order_number', 'like', $term);
            });
        }

        if (in_array('period', $allowedFilters, true)) {
            $period = $request->get('period', 'this_year');
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $period = 'custom';
            }
            $this->applyPeriodFilter($query, $period, $request);
        }

        $orders = $query->get();

        if ($withInvoices) {
            $this->eagerLoadInvoicesForOrders($orders);
        }

        if (in_array('split_type', $allowedFilters, true) && $request->filled('split_type') && in_array($request->split_type, ['conjunto', 'individual'], true)) {
            $orders = $orders->filter(function (Order $order) use ($request) {
                $label = $order->splitTypeLabel();

                return $request->split_type === 'conjunto'
                    ? $label === 'Conjunto'
                    : $label === 'Individual';
            })->values();
        }

        if (in_array('origin_store_id', $allowedFilters, true) && ($originStoreId = $this->resolveStoreFilterId($request->input('origin_store_id'))) !== null) {
            $this->authorizeStoreAccess($originStoreId);
            $orders = $orders->filter(fn (Order $order) => $order->originStoreId() === $originStoreId)->values();
        }

        [$sortBy, $sortDir] = $this->resolveSortParams($request, [
            'status' => 'asc',
            'date' => 'desc',
            'store' => 'asc',
            'invoice_number' => 'asc',
            'order_number' => 'asc',
            'concept' => 'asc',
            'amount' => 'desc',
            'total_paid' => 'desc',
            'pending' => 'desc',
        ], 'date', 'desc');

        $orders = $this->sortOrderCollection($orders, $sortBy, $sortDir);

        $originStoreIds = $orders->map(fn (Order $order) => $order->originStoreId())->unique()->filter()->values();
        $originStoresById = Store::whereIn('id', $originStoreIds)->pluck('name', 'id');

        return [
            'orders' => $orders,
            'originStoresById' => $originStoresById,
            'allowedFilters' => $allowedFilters,
        ];
    }

    private function supplierOrderExportCell(Order $order, string $key, $originStoresById): string
    {
        return match ($key) {
            'status' => ucfirst((string) $order->status),
            'date' => $order->date?->format('d/m/Y') ?? '',
            'store' => (string) ($order->store->name ?? ''),
            'split_type' => $order->splitTypeLabel(),
            'origin_store' => (string) ($originStoresById[$order->originStoreId()] ?? ''),
            'invoice_number' => (string) ($order->invoice_number ?? ''),
            'order_number' => (string) ($order->order_number ?? ''),
            'concept' => ucfirst((string) ($order->concept ?? '')),
            'amount' => number_format((float) $order->amount, 2, ',', '.'),
            'total_paid' => number_format((float) $order->total_paid, 2, ',', '.'),
            'pending' => number_format((float) $order->pending_amount, 2, ',', '.'),
            'payment_methods' => $order->payments->pluck('method')->unique()->map(function ($method) {
                return match ($method) {
                    'cash' => 'Efectivo',
                    'transfer', 'bank' => 'Transferencia',
                    'card' => 'Tarjeta',
                    default => ucfirst((string) ($method ?? '')),
                };
            })->unique()->values()->filter()->implode(', '),
            default => '',
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>|\Illuminate\Database\Eloquent\Collection<int, Order>  $orders
     */
    private function eagerLoadInvoicesForOrders($orders): void
    {
        $numbers = $orders->pluck('invoice_number')->filter()->unique()->values();
        if ($numbers->isEmpty()) {
            foreach ($orders as $order) {
                $order->setRelation('invoice', null);
            }

            return;
        }

        $invoices = \App\Models\Invoice::query()
            ->whereIn('invoice_number', $numbers)
            ->get()
            ->keyBy('invoice_number');

        foreach ($orders as $order) {
            $order->setRelation('invoice', $invoices->get($order->invoice_number));
        }
    }

    private function applyPeriodFilter($query, $period, $request = null)
    {
        // Personalizado: usar date_from y date_to del request
        if ($request && $request->filled('date_from') && $request->filled('date_to')) {
            try {
                $start = \Carbon\Carbon::parse($request->date_from)->startOfDay();
                $end = \Carbon\Carbon::parse($request->date_to)->endOfDay();
                $query->whereBetween('date', [$start, $end]);
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
            default:
                $start = now()->subDays(29)->startOfDay();
        }

        $query->whereBetween('date', [$start, $end]);
    }

    private function calculateSummaryByStore($orders)
    {
        $summaryByStore = [];

        // Agrupar pedidos por store_id
        $ordersByStore = $orders->groupBy('store_id');

        foreach ($ordersByStore as $storeId => $storeOrders) {
            $store = $storeOrders->first()->store;
            
            $totalPaid = 0;
            $totalPending = 0;
            
            foreach ($storeOrders as $order) {
                $totalPaid += $order->total_paid;
                $totalPending += $order->pending_amount;
            }

            $summaryByStore[] = [
                'store_id' => $storeId,
                'store_name' => $store ? $store->name : 'Sin tienda',
                'total_orders' => $storeOrders->count(),
                'total_amount' => $storeOrders->sum('amount'),
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending,
            ];
        }

        // Ordenar por nombre de tienda
        usort($summaryByStore, function($a, $b) {
            return strcmp($a['store_name'], $b['store_name']);
        });

        return $summaryByStore;
    }

    private function calculateSummaryBySupplier($orders)
    {
        $summaryBySupplier = [];

        $ordersBySupplier = $orders->filter(fn ($o) => $o->supplier_id)->groupBy('supplier_id');

        foreach ($ordersBySupplier as $supplierId => $supplierOrders) {
            $supplier = $supplierOrders->first()->supplier;

            $totalPaid = 0;
            $totalPending = 0;

            foreach ($supplierOrders as $order) {
                $totalPaid += $order->total_paid;
                $totalPending += $order->pending_amount;
            }

            $summaryBySupplier[] = [
                'supplier_id' => $supplierId,
                'supplier_name' => $supplier ? $supplier->name : 'Sin proveedor',
                'total_orders' => $supplierOrders->count(),
                'total_amount' => $supplierOrders->sum('amount'),
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending,
            ];
        }

        // Órdenes sin proveedor
        $ordersWithoutSupplier = $orders->filter(fn ($o) => !$o->supplier_id);
        if ($ordersWithoutSupplier->isNotEmpty()) {
            $totalPaid = 0;
            $totalPending = 0;
            foreach ($ordersWithoutSupplier as $order) {
                $totalPaid += $order->total_paid;
                $totalPending += $order->pending_amount;
            }
            $summaryBySupplier[] = [
                'supplier_id' => null,
                'supplier_name' => 'Sin proveedor',
                'total_orders' => $ordersWithoutSupplier->count(),
                'total_amount' => $ordersWithoutSupplier->sum('amount'),
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending,
            ];
        }

        usort($summaryBySupplier, function ($a, $b) {
            return strcmp($a['supplier_name'], $b['supplier_name']);
        });

        return $summaryBySupplier;
    }

    /**
     * @param  array<string, string>  $columnsWithDefaultDir
     * @return array{0: string, 1: string}
     */
    private function resolveSortParams(
        Request $request,
        array $columnsWithDefaultDir,
        string $fallbackColumn,
        string $fallbackDir,
        string $sortByKey = 'sort_by',
        string $sortDirKey = 'sort_dir'
    ): array {
        $sortBy = (string) $request->get($sortByKey, $fallbackColumn);
        if (! array_key_exists($sortBy, $columnsWithDefaultDir)) {
            $sortBy = $fallbackColumn;
        }

        $defaultDir = $columnsWithDefaultDir[$sortBy] ?? $fallbackDir;
        $sortDir = strtolower((string) $request->get($sortDirKey, $defaultDir));
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = $defaultDir;
        }

        return [$sortBy, $sortDir];
    }

    private function sortSuppliersWithStats(array $rows, string $sortBy, string $sortDir): array
    {
        $mult = $sortDir === 'desc' ? -1 : 1;

        usort($rows, function ($a, $b) use ($sortBy, $mult) {
            $cmp = match ($sortBy) {
                'supplier' => strcasecmp($a['supplier']->name ?? '', $b['supplier']->name ?? ''),
                'type' => strcasecmp(
                    Supplier::TYPES[$a['supplier']->type ?? ''] ?? ($a['supplier']->type ?? ''),
                    Supplier::TYPES[$b['supplier']->type ?? ''] ?? ($b['supplier']->type ?? '')
                ),
                'total_orders' => $a['total_orders'] <=> $b['total_orders'],
                'total_amount' => (float) $a['total_amount'] <=> (float) $b['total_amount'],
                'total_paid' => (float) $a['total_paid'] <=> (float) $b['total_paid'],
                'total_pending' => (float) $a['total_pending'] <=> (float) $b['total_pending'],
                default => 0,
            };

            return $mult * $cmp;
        });

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>|\Illuminate\Database\Eloquent\Collection<int, Order>  $orders
     */
    private function sortOrderCollection($orders, string $sortBy, string $sortDir)
    {
        $mult = $sortDir === 'desc' ? -1 : 1;

        return $orders->sort(function ($a, $b) use ($sortBy, $mult) {
            $cmp = match ($sortBy) {
                'status' => strcmp($a->status, $b->status),
                'date' => $a->date <=> $b->date,
                'store' => strcasecmp($a->store?->name ?? '', $b->store?->name ?? ''),
                'invoice_number' => strcasecmp((string) ($a->invoice_number ?? ''), (string) ($b->invoice_number ?? '')),
                'order_number' => strcasecmp((string) ($a->order_number ?? ''), (string) ($b->order_number ?? '')),
                'concept' => strcasecmp((string) ($a->concept ?? ''), (string) ($b->concept ?? '')),
                'amount' => (float) $a->amount <=> (float) $b->amount,
                'total_paid' => (float) $a->total_paid <=> (float) $b->total_paid,
                'pending' => (float) $a->pending_amount <=> (float) $b->pending_amount,
                default => $a->date <=> $b->date,
            };

            if ($cmp === 0) {
                return $b->id <=> $a->id;
            }

            return $mult * $cmp;
        })->values();
    }

    private function sortSummaryByStore(array $rows, string $sortBy, string $sortDir): array
    {
        $mult = $sortDir === 'desc' ? -1 : 1;

        usort($rows, function ($a, $b) use ($sortBy, $mult) {
            $cmp = match ($sortBy) {
                'store_name' => strcmp($a['store_name'] ?? '', $b['store_name'] ?? ''),
                'store_total_orders' => ($a['total_orders'] ?? 0) <=> ($b['total_orders'] ?? 0),
                'store_total_amount' => (float) ($a['total_amount'] ?? 0) <=> (float) ($b['total_amount'] ?? 0),
                'store_total_paid' => (float) ($a['total_paid'] ?? 0) <=> (float) ($b['total_paid'] ?? 0),
                'store_total_pending' => (float) ($a['total_pending'] ?? 0) <=> (float) ($b['total_pending'] ?? 0),
                default => strcmp($a['store_name'] ?? '', $b['store_name'] ?? ''),
            };

            return $mult * $cmp;
        });

        return $rows;
    }

    private function calculateSummary($orders)
    {
        $summary = [
            'total_orders' => $orders->count(),
            'total_amount' => $orders->sum('amount'),
            'total_paid' => 0,
            'total_pending' => 0,
        ];

        foreach ($orders as $order) {
            $summary['total_paid'] += $order->total_paid;
            $summary['total_pending'] += $order->pending_amount;
        }

        return $summary;
    }

    private function addHistoryEntry(Order $order, string $action, array $oldData = [])
    {
        $history = $order->history ?? [];
        $history[] = [
            'action' => $action,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'timestamp' => now()->timestamp,
            'changes' => $oldData ? ['old' => $oldData, 'new' => $order->toArray()] : null,
        ];

        $order->update(['history' => $history]);
    }

    /**
     * Sincroniza pagos del pedido in-place: update / create / forceDelete sobrantes.
     *
     * @param  array<int, array<string, mixed>>  $paymentsInput
     */
    private function syncOrderPayments(Order $order, array $paymentsInput, ?int $cashStoreFallback = null): void
    {
        $cashStoreFallback = $cashStoreFallback ?? (int) $order->store_id;
        $existing = $order->payments()->get()->keyBy('id');
        $keptIds = [];

        foreach ($paymentsInput as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }
            $data = $this->paymentDataForOrder($payment, $amount, $cashStoreFallback);
            $id = isset($payment['id']) ? (int) $payment['id'] : 0;

            if ($id > 0 && $existing->has($id)) {
                $existing->get($id)->update($data);
                $keptIds[] = $id;
            } else {
                $created = $order->payments()->create($data);
                $keptIds[] = (int) $created->id;
            }
        }

        foreach ($existing as $id => $payment) {
            if (! in_array((int) $id, $keptIds, true)) {
                $payment->forceDelete();
            }
        }

        $order->unsetRelation('payments');
    }

    /**
     * Escala pagos del formulario de forma proporcional al importe de un tramo del split.
     *
     * @param  array<int, array<string, mixed>>  $paymentsInput
     * @param  array<int, int>  $existingPaymentIds  ids existentes (por índice) para reutilizar
     * @return array<int, array<string, mixed>>
     */
    private function proportionalPaymentsInput(
        array $paymentsInput,
        float $totalAmount,
        float $storeAmount,
        array $existingPaymentIds = []
    ): array {
        if ($totalAmount <= 0) {
            return [];
        }

        $result = [];
        $i = 0;
        foreach ($paymentsInput as $payment) {
            $paymentAmount = (float) ($payment['amount'] ?? 0);
            if ($paymentAmount <= 0) {
                continue;
            }
            $row = $payment;
            $row['amount'] = round(($paymentAmount / $totalAmount) * $storeAmount, 2);
            if (! empty($existingPaymentIds[$i])) {
                $row['id'] = $existingPaymentIds[$i];
            }
            $result[] = $row;
            $i++;
        }

        return $result;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Order>
     */
    private function findSplitRelatedOrders(Order $order)
    {
        if (! $order->store_split) {
            return collect([$order]);
        }

        return Order::query()
            ->where('order_number', $order->order_number)
            ->where(function ($q) use ($order) {
                if ($order->invoice_number === null || $order->invoice_number === '') {
                    $q->whereNull('invoice_number')->orWhere('invoice_number', '');
                } else {
                    $q->where('invoice_number', $order->invoice_number);
                }
            })
            ->whereNotNull('store_split')
            ->get();
    }

    /**
     * Elimina pedido, pagos y gastos derivados sin pasar por papelera (efecto de sync/edición).
     */
    private function forceDeleteOrderCascade(Order $order): void
    {
        $this->forceDeleteOrderLinkedExpenses($order->id);
        $order->payments()->withTrashed()->get()->each->forceDelete();
        $order->forceDelete();
    }

    private function forceDeleteOrderLinkedExpenses(int $orderId): void
    {
        $entries = $this->getOrderLinkedExpenses($orderId);
        if ($entries->isEmpty()) {
            return;
        }
        CashWalletExpense::whereIn('financial_entry_id', $entries->pluck('id'))->delete();
        foreach ($entries as $entry) {
            $entry->forceDelete();
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, FinancialEntry>
     */
    private function getOrderLinkedExpenses(int $orderId)
    {
        return FinancialEntry::query()
            ->where('type', 'expense')
            ->where('notes', 'like', '%"order_id":'.$orderId.'%')
            ->get()
            ->filter(function (FinancialEntry $entry) use ($orderId) {
                $notes = $this->decodeEntryNotes($entry->notes);

                return (int) ($notes['order_id'] ?? 0) === $orderId
                    && ($notes['source'] ?? null) === 'order';
            })
            ->values();
    }

    /**
     * @param  mixed  $notes
     * @return array<string, mixed>
     */
    private function decodeEntryNotes($notes): array
    {
        if ($notes === null || $notes === '') {
            return [];
        }
        if (is_array($notes)) {
            return $notes;
        }
        $decoded = @json_decode((string) $notes, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Crear o actualizar gastos del pedido in-place (sin soft-delete en sync).
     */
    private function syncExpensesFromOrder(Order $order): void
    {
        $order->refresh();
        $order->load('payments', 'supplier.expenseCategory');

        $expenseCategoryName = $order->supplier?->expenseCategory?->name ?? $this->mapConceptToCategory($order->concept);
        $totalAmount = (float) $order->amount;
        $existing = $this->getOrderLinkedExpenses((int) $order->id);
        $usedIds = [];

        DB::beginTransaction();
        try {
            if ($order->payments->isEmpty()) {
                $pending = $existing->first(function (FinancialEntry $entry) {
                    $notes = $this->decodeEntryNotes($entry->notes);

                    return empty($notes['order_payment_id']);
                }) ?? $existing->first();

                $payload = [
                    'date' => $order->date,
                    'store_id' => $order->store_id,
                    'supplier_id' => $order->supplier_id,
                    'type' => 'expense',
                    'total_amount' => $totalAmount,
                    'amount' => $totalAmount,
                    'expense_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'status' => 'pendiente',
                    'expense_category' => $expenseCategoryName,
                    'expense_source' => 'pedido',
                    'expense_concept' => $this->generateExpenseConcept($order),
                    'expense_payment_method' => 'bank',
                    'expense_paid_cash' => false,
                    'notes' => json_encode([
                        'order_id' => $order->id,
                        'order_invoice_number' => $order->invoice_number,
                        'order_number' => $order->order_number,
                        'source' => 'order',
                    ]),
                ];

                if ($pending) {
                    $pending->update($payload);
                    CashWalletExpense::where('financial_entry_id', $pending->id)->delete();
                    $usedIds[] = (int) $pending->id;
                } else {
                    $payload['created_by'] = Auth::id();
                    $created = FinancialEntry::create($payload);
                    $usedIds[] = (int) $created->id;
                }
            } else {
                $conceptBase = $this->generateExpenseConcept($order);

                foreach ($order->payments as $payment) {
                    $amount = (float) $payment->amount;
                    $expensePaymentMethod = $this->mapPaymentMethod($payment->method);
                    $isCash = ($payment->method === 'cash');
                    $storeId = (int) $order->store_id;
                    if ($isCash && $payment->cash_source === 'store' && $payment->cash_store_id) {
                        $storeId = (int) $payment->cash_store_id;
                    }

                    $paymentLabel = $payment->method === 'card'
                        ? 'Tarjeta'
                        : ($payment->method === 'transfer' ? 'Transferencia' : ucfirst((string) $payment->method));
                    $expenseConcept = $conceptBase.' - '.$paymentLabel;

                    $match = $existing->first(function (FinancialEntry $entry) use ($payment) {
                        $notes = $this->decodeEntryNotes($entry->notes);

                        return (int) ($notes['order_payment_id'] ?? 0) === (int) $payment->id;
                    });

                    $payload = [
                        'date' => $payment->date,
                        'store_id' => $storeId,
                        'supplier_id' => $order->supplier_id,
                        'type' => 'expense',
                        'total_amount' => $amount,
                        'amount' => $amount,
                        'expense_amount' => $amount,
                        'paid_amount' => $amount,
                        'status' => 'pagado',
                        'expense_category' => $expenseCategoryName,
                        'expense_source' => 'pedido',
                        'expense_concept' => $expenseConcept,
                        'expense_payment_method' => $expensePaymentMethod,
                        'expense_paid_cash' => $isCash,
                        'notes' => json_encode([
                            'order_id' => $order->id,
                            'order_invoice_number' => $order->invoice_number,
                            'order_number' => $order->order_number,
                            'order_payment_id' => $payment->id,
                            'payment_method' => $payment->method,
                            'source' => 'order',
                        ]),
                    ];

                    if ($match) {
                        $match->update($payload);
                        $entry = $match;
                    } else {
                        $payload['created_by'] = Auth::id();
                        $entry = FinancialEntry::create($payload);
                    }

                    $usedIds[] = (int) $entry->id;
                    $this->syncCashWalletExpenseForPayment($entry, $payment, $amount);
                }
            }

            foreach ($existing as $entry) {
                if (! in_array((int) $entry->id, $usedIds, true)) {
                    CashWalletExpense::where('financial_entry_id', $entry->id)->delete();
                    $entry->forceDelete();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** @deprecated Use syncExpensesFromOrder */
    private function createExpensesFromOrder(Order $order): void
    {
        $this->syncExpensesFromOrder($order);
    }

    private function syncCashWalletExpenseForPayment(FinancialEntry $entry, $payment, float $amount): void
    {
        $wantsWallet = ($payment->method === 'cash')
            && ($payment->cash_source === 'wallet')
            && ! empty($payment->cash_wallet_id);

        $existingLink = CashWalletExpense::where('financial_entry_id', $entry->id)->first();

        if (! $wantsWallet) {
            if ($existingLink) {
                $existingLink->delete();
            }

            return;
        }

        $wallet = CashWallet::find($payment->cash_wallet_id);
        if (! $wallet) {
            if ($existingLink) {
                $existingLink->delete();
            }

            return;
        }

        if ($existingLink) {
            $existingLink->update([
                'cash_wallet_id' => $wallet->id,
                'amount' => $amount,
            ]);

            return;
        }

        CashWalletExpense::create([
            'cash_wallet_id' => $wallet->id,
            'financial_entry_id' => $entry->id,
            'amount' => $amount,
        ]);
    }

    private function validateOrderPaymentsCashSource(array $payments): void
    {
        foreach ($payments as $i => $p) {
            if (($p['method'] ?? '') !== 'cash') {
                continue;
            }
            $source = $p['cash_source'] ?? null;
            // Si no eligió procedencia, se usará "tienda" por defecto en paymentDataForOrder
            if (! $source || ! in_array($source, ['wallet', 'store'], true)) {
                continue;
            }
            if ($source === 'wallet' && empty($p['cash_wallet_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "payments.{$i}.cash_wallet_id" => ['Seleccione la cartera de la que sale el efectivo.'],
                ]);
            }
            // store sin cash_store_id se rellena con la tienda del pedido en paymentDataForOrder
        }
    }

    private function resolveStoreFilterId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function paymentDataForOrder(array $payment, float $amount, int $orderStoreId): array
    {
        $data = [
            'date' => $payment['date'],
            'method' => $payment['method'],
            'amount' => $amount,
        ];
        if (($payment['method'] ?? '') === 'cash') {
            $source = $payment['cash_source'] ?? null;
            $data['cash_source'] = in_array($source, ['wallet', 'store'], true) ? $source : 'store';
            $data['cash_wallet_id'] = ! empty($payment['cash_wallet_id']) ? (int) $payment['cash_wallet_id'] : null;
            $data['cash_store_id'] = ! empty($payment['cash_store_id']) ? (int) $payment['cash_store_id'] : null;
            if ($data['cash_source'] === 'store' && empty($data['cash_store_id'])) {
                $data['cash_store_id'] = $orderStoreId;
            }
        } else {
            $data['cash_source'] = null;
            $data['cash_wallet_id'] = null;
            $data['cash_store_id'] = null;
        }

        return $data;
    }

    private function calculateWalletBalance(CashWallet $wallet): float
    {
        $withdrawals = \App\Models\CashWithdrawal::where('cash_wallet_id', $wallet->id)->sum('amount');
        $expenses = CashWalletExpense::where('cash_wallet_id', $wallet->id)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('financial_entries')
                    ->whereColumn('financial_entries.id', 'cash_wallet_expenses.financial_entry_id');
            })
            ->sum('amount');
        $transfers = \App\Models\CashWalletTransfer::where('cash_wallet_id', $wallet->id)->sum('amount');
        $bankToWallet = (float) (\App\Models\Transfer::where('destination_type', 'wallet')
            ->where('destination_id', $wallet->id)
            ->where('destination_fund', 'cash')
            ->where('origin_type', 'store')
            ->where('origin_fund', 'bank')
            ->where('status', 'reconciled')
            ->sum('amount') ?? 0);

        return (float) $withdrawals - (float) $expenses - (float) $transfers + $bankToWallet;
    }

    private function mapPaymentMethod(string $orderMethod): string
    {
        $mapping = [
            'cash' => 'cash',
            'bank' => 'bank',
            'transfer' => 'bank',
            'card' => 'bank',
        ];

        return $mapping[$orderMethod] ?? 'bank';
    }

    private function mapConceptToCategory(string $concept): string
    {
        $mapping = [
            'pedido' => 'compras',
            'royalty' => 'royalty',
            'rectificacion' => 'compras',
            'tara' => 'compras',
        ];

        return $mapping[$concept] ?? 'otros';
    }

    private function generateExpenseConcept(Order $order): string
    {
        $conceptLabels = [
            'pedido' => 'Pedido',
            'royalty' => 'Royalty',
            'rectificacion' => 'Rectificación',
            'tara' => 'Tara',
        ];

        $conceptLabel = $conceptLabels[$order->concept] ?? 'Pedido';
        
        return sprintf(
            '%s - Factura: %s / Pedido: %s',
            $conceptLabel,
            $order->invoice_number,
            $order->order_number
        );
    }
}
