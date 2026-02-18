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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;
    
    public function __construct()
    {
        $this->middleware('permission:orders.main.view')->only(['index', 'show', 'supplierOrders']);
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

        $ordersQuery = Order::with(['store', 'supplier', 'payments']);
        $this->scopeStoreForCurrentUser($ordersQuery);
        if ($request->has('period')) {
            $this->applyPeriodFilter($ordersQuery, $request->period);
        }
        $orders = $ordersQuery->get();

        $summaryBySupplier = $this->calculateSummaryBySupplier($orders);

        // Solo proveedores con ID que tienen al menos un pedido
        $suppliersWithStats = collect($summaryBySupplier)
            ->filter(fn ($s) => !empty($s['supplier_id']) && ($s['total_orders'] ?? 0) > 0)
            ->map(function ($s) {
                $supplier = Supplier::find($s['supplier_id']);
                if (!$supplier) {
                    return null;
                }
                return [
                    'supplier' => $supplier,
                    'total_orders' => $s['total_orders'],
                    'total_amount' => $s['total_amount'],
                    'total_paid' => $s['total_paid'],
                    'total_pending' => $s['total_pending'],
                ];
            })
            ->filter()
            ->values()
            ->all();

        return view('orders.index', compact('suppliersWithStats'));
    }

    /**
     * Vista de segundo nivel: pedidos de un proveedor concreto.
     */
    public function supplierOrders(Request $request, Supplier $supplier)
    {
        $this->syncStoresFromBusinesses();

        $query = Order::with(['store', 'supplier', 'payments'])
            ->where('supplier_id', $supplier->id);
        $this->scopeStoreForCurrentUser($query);

        if ($request->has('period')) {
            $this->applyPeriodFilter($query, $request->period);
        }

        $orders = $query->orderBy('date', 'desc')->get();
        $summary = $this->calculateSummary($orders);
        $summaryByStore = $this->calculateSummaryByStore($orders);

        return view('orders.supplier', compact('supplier', 'orders', 'summary', 'summaryByStore'));
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
            'payments.*.date' => 'required|date',
            'payments.*.method' => 'required|in:cash,bank,transfer,card',
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
                        'total' => $totalAmount
                    ];
                    
                    $order = Order::create($orderData);
                    
                    // Crear pagos proporcionales si existen
                    if ($request->has('payments')) {
                        foreach ($request->payments as $payment) {
                            $paymentAmount = (float) ($payment['amount'] ?? 0);
                            $proportionalAmount = ($paymentAmount / $totalAmount) * $storeAmount;
                            $order->payments()->create($this->paymentDataForOrder($payment, round($proportionalAmount, 2), $storeId));
                        }
                    }
                    
                    $this->addHistoryEntry($order, 'created');
                    
                    // Crear gastos automáticamente para el pedido
                    $this->createExpensesFromOrder($order);
                    
                    $createdOrders[] = $order;
                }
            }
            
            if (count($createdOrders) > 0) {
                return redirect()->route('orders.index')->with('success', 
                    'Pedido dividido entre ' . count($createdOrders) . ' tienda' . (count($createdOrders) > 1 ? 's' : '') . ' creado correctamente.');
            }
        }

        $order = Order::create($validated);

        if ($request->has('payments')) {
            foreach ($request->payments as $payment) {
                $order->payments()->create($this->paymentDataForOrder($payment, (float) ($payment['amount'] ?? 0), $order->store_id));
            }
        }

        $this->addHistoryEntry($order, 'created');
        
        // Crear gastos automáticamente para el pedido (cartera, efectivo, banco)
        $this->createExpensesFromOrder($order);

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
            'payments.*.date' => 'required|date',
            'payments.*.method' => 'required|in:cash,bank,transfer,card',
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
            
            // Si no hay cantidades específicas, dividir por partes iguales
            if (empty($splitAmounts)) {
                $amountPerStore = $totalAmount / count($splitStores);
                foreach ($splitStores as $storeId) {
                    $splitAmounts[$storeId] = round($amountPerStore, 2);
                }
            }
            
            // Si el pedido actual tiene división, eliminar los pedidos relacionados
            if ($order->store_split) {
                // Buscar otros pedidos relacionados por el mismo número de factura y pedido
                Order::where('id', '!=', $order->id)
                    ->where('invoice_number', $order->invoice_number)
                    ->where('order_number', $order->order_number)
                    ->where('store_split', '!=', null)
                    ->delete();
            }
            
            // Actualizar el pedido actual con la primera tienda
            $firstStoreId = $splitStores[0];
            $firstAmount = (float) ($splitAmounts[$firstStoreId] ?? 0);
            
            $validated['store_id'] = $firstStoreId;
            $validated['amount'] = $firstAmount;
            $validated['store_split'] = [
                'stores' => $splitStores,
                'amounts' => $splitAmounts,
                'total' => $totalAmount
            ];
            
            $order->update($validated);
            
            // Actualizar pagos proporcionales
            if ($request->has('payments')) {
                $order->payments()->delete();
                foreach ($request->payments as $payment) {
                    $paymentAmount = (float) ($payment['amount'] ?? 0);
                    $proportionalAmount = ($paymentAmount / $totalAmount) * $firstAmount;
                    $order->payments()->create($this->paymentDataForOrder($payment, round($proportionalAmount, 2), $firstStoreId));
                }
            }
            
            // Crear gastos automáticamente para el pedido
            $this->createExpensesFromOrder($order);
            
            // Crear pedidos para las demás tiendas
            $createdCount = 1;
            for ($i = 1; $i < count($splitStores); $i++) {
                $storeId = $splitStores[$i];
                $storeAmount = (float) ($splitAmounts[$storeId] ?? 0);
                if ($storeAmount > 0) {
                    $orderData = $validated;
                    $orderData['store_id'] = $storeId;
                    $orderData['amount'] = $storeAmount;
                    
                    $newOrder = Order::create($orderData);
                    
                    // Crear pagos proporcionales
                    if ($request->has('payments')) {
                        foreach ($request->payments as $payment) {
                            $paymentAmount = (float) ($payment['amount'] ?? 0);
                            $proportionalAmount = ($paymentAmount / $totalAmount) * $storeAmount;
                            $newOrder->payments()->create($this->paymentDataForOrder($payment, round($proportionalAmount, 2), $storeId));
                        }
                    }
                    
                    $this->addHistoryEntry($newOrder, 'created');
                    
                    // Crear gastos automáticamente para el pedido
                    $this->createExpensesFromOrder($newOrder);
                    
                    $createdCount++;
                }
            }
            
            $this->addHistoryEntry($order, 'updated', $oldData);
            
            return redirect()->route('orders.index')->with('success', 
                'Pedido dividido entre ' . $createdCount . ' tienda' . ($createdCount > 1 ? 's' : '') . ' actualizado correctamente.');
        }

        $order->update($validated);

        if ($request->has('payments')) {
            $order->payments()->delete();
            foreach ($request->payments as $payment) {
                $order->payments()->create($this->paymentDataForOrder($payment, (float) ($payment['amount'] ?? 0), $order->store_id));
            }
        }

        $this->addHistoryEntry($order, 'updated', $oldData);
        
        // Crear o actualizar gastos automáticamente para el pedido (cartera, efectivo, banco)
        $this->createExpensesFromOrder($order);

        $redirectTo = $order->supplier_id
            ? route('orders.supplier', $order->supplier)
            : route('orders.index');
        return redirect($redirectTo)->with('success', 'Pedido actualizado correctamente.');
    }

    public function destroy(Order $order)
    {
        // Eliminar todos los gastos asociados a este pedido (CashWalletExpense se elimina por cascade)
        FinancialEntry::where('type', 'expense')
            ->where('notes', 'like', '%"order_id":' . $order->id . '%')
            ->delete();
        
        // Si es un pedido dividido, eliminar también los pedidos relacionados
        if ($order->store_split) {
            Order::where('id', '!=', $order->id)
                ->where('invoice_number', $order->invoice_number)
                ->where('order_number', $order->order_number)
                ->where('store_split', '!=', null)
                ->get()
                ->each(function ($relatedOrder) {
                    FinancialEntry::where('type', 'expense')
                        ->where('notes', 'like', '%"order_id":' . $relatedOrder->id . '%')
                        ->delete();
                    $relatedOrder->delete();
                });
        }
        
        $this->addHistoryEntry($order, 'deleted');
        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Pedido eliminado correctamente.');
    }

    private function applyPeriodFilter($query, $period)
    {
        $end = now()->endOfDay();
        
        switch ($period) {
            case 'last_7':
                $start = now()->subDays(6)->startOfDay();
                break;
            case 'last_30':
                $start = now()->subDays(29)->startOfDay();
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
     * Crear gastos automáticamente desde un pedido
     * Crea un gasto por cada método de pago utilizado (efectivo y banco)
     */
    private function createExpensesFromOrder(Order $order)
    {
        // Recargar el pedido y cargar explícitamente la relación de pagos
        $order->refresh();
        $order->load('payments');
        
        // Calcular total pagado
        $totalPaid = $order->payments->sum('amount');
        $totalAmount = $order->amount;
        
        // Eliminar gastos existentes de este pedido para recrearlos
        FinancialEntry::where('type', 'expense')
            ->where('notes', 'like', '%"order_id":' . $order->id . '%')
            ->delete();
        
        // Si no hay pagos, crear un solo gasto pendiente con el importe total
        if ($order->payments->isEmpty()) {
            FinancialEntry::create([
                'date' => $order->date,
                'store_id' => $order->store_id,
                'supplier_id' => $order->supplier_id,
                'type' => 'expense',
                'total_amount' => $totalAmount,
                'amount' => $totalAmount,
                'expense_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'pendiente',
                'expense_category' => $this->mapConceptToCategory($order->concept),
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
                'created_by' => Auth::id(),
            ]);
            return;
        }
        
        $conceptBase = $this->generateExpenseConcept($order);
        $category = $this->mapConceptToCategory($order->concept);

        DB::beginTransaction();
        try {
            foreach ($order->payments as $payment) {
                $amount = (float) $payment->amount;
                $expensePaymentMethod = $this->mapPaymentMethod($payment->method);
                $isCash = ($payment->method === 'cash');
                $storeId = $order->store_id;
                if ($isCash && $payment->cash_source === 'store' && $payment->cash_store_id) {
                    $storeId = (int) $payment->cash_store_id;
                }

                $paymentLabel = $payment->method === 'card' ? 'Tarjeta' : ($payment->method === 'transfer' ? 'Transferencia' : ucfirst($payment->method));
                $expenseConcept = $conceptBase . ' - ' . $paymentLabel;

                $financialEntry = FinancialEntry::create([
                    'date' => $payment->date,
                    'store_id' => $storeId,
                    'supplier_id' => $order->supplier_id,
                    'type' => 'expense',
                    'total_amount' => $amount,
                    'amount' => $amount,
                    'expense_amount' => $amount,
                    'paid_amount' => $amount,
                    'status' => 'pagado',
                    'expense_category' => $category,
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
                    'created_by' => Auth::id(),
                ]);

                if ($isCash && $payment->cash_source === 'wallet' && $payment->cash_wallet_id) {
                    $wallet = CashWallet::find($payment->cash_wallet_id);
                    if ($wallet) {
                        $balance = $this->calculateWalletBalance($wallet);
                        if ($balance < $amount) {
                            DB::rollBack();
                            throw new \Exception("La cartera \"{$wallet->name}\" no tiene saldo suficiente. Saldo: " . number_format($balance, 2, ',', '.') . ' €');
                        }
                        CashWalletExpense::create([
                            'cash_wallet_id' => $wallet->id,
                            'financial_entry_id' => $financialEntry->id,
                            'amount' => $amount,
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validateOrderPaymentsCashSource(array $payments): void
    {
        foreach ($payments as $i => $p) {
            if (($p['method'] ?? '') !== 'cash') {
                continue;
            }
            $source = $p['cash_source'] ?? null;
            if (!$source || !in_array($source, ['wallet', 'store'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "payments.{$i}.cash_source" => ['Cuando el método de pago es efectivo debe indicar la procedencia: Cartera o Tienda.'],
                ]);
            }
            if ($source === 'wallet' && empty($p['cash_wallet_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "payments.{$i}.cash_wallet_id" => ['Seleccione la cartera de la que sale el efectivo.'],
                ]);
            }
            if ($source === 'store' && empty($p['cash_store_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "payments.{$i}.cash_store_id" => ['Seleccione la tienda de la que sale el efectivo.'],
                ]);
            }
        }
    }

    private function paymentDataForOrder(array $payment, float $amount, int $orderStoreId): array
    {
        $data = [
            'date' => $payment['date'],
            'method' => $payment['method'],
            'amount' => $amount,
        ];
        if (($payment['method'] ?? '') === 'cash') {
            $data['cash_source'] = $payment['cash_source'] ?? null;
            $data['cash_wallet_id'] = !empty($payment['cash_wallet_id']) ? (int) $payment['cash_wallet_id'] : null;
            $data['cash_store_id'] = !empty($payment['cash_store_id']) ? (int) $payment['cash_store_id'] : null;
            if ($data['cash_source'] === 'store' && empty($data['cash_store_id'])) {
                $data['cash_store_id'] = $orderStoreId;
            }
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
        return (float) $withdrawals - (float) $expenses - (float) $transfers;
    }

    private function mapPaymentMethod(string $orderMethod): string
    {
        $mapping = [
            'cash' => 'cash',
            'bank' => 'bank',
            'transfer' => 'bank',
            'card' => 'card',
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
