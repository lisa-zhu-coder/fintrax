<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\CashControlDayComment;
use App\Models\CashWallet;
use App\Models\CashWalletExpense;
use App\Models\CashWalletTransfer;
use App\Models\CashWithdrawal;
use App\Models\Company;
use App\Models\ExpensePayment;
use App\Models\FinancialEntry;
use App\Models\OrderPayment;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FinancialController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:financial.registros.view')->only(['index']);
        $this->middleware('permission:financial.income.view')->only(['income']);
        $this->middleware('permission:financial.expenses.view')->only(['expenses']);
        $this->middleware('permission:financial.daily_closes.view')->only(['dailyCloses', 'exportDailyCloses']);
        $this->middleware('permission:financial.registros.create')->only(['create', 'store']);
        $this->middleware('permission:financial.expenses.create')->only(['storeTpvDifferenceExpense']);
        // show, edit, update: autorización por tipo de registro en el propio método
        $this->middleware('permission:financial.registros.delete')->only(['destroy']);
        $this->middleware('permission:financial.registros.export')->only('export');
        $this->middleware('permission:treasury.cash_control.view')->only(['cashControl', 'cashControlStore', 'cashControlMonth']);
        $this->middleware('permission:treasury.cash_control.create')->only(['storeCashControlExpense']);
        $this->middleware('permission:treasury.cash_control.edit')->only(['updateCashReal', 'storeCashControlDayComment']);
        $this->middleware('permission:treasury.bank_control.view')->only(['bankControl', 'bankImportForm', 'downloadBankImportTemplate', 'bankImportStore']);
        $this->middleware('permission:treasury.bank_conciliation.view')->only(['bankConciliation', 'getAvailableExpenses', 'getAvailableIncomes']);
        $this->middleware('permission:treasury.bank_conciliation.edit')->only(['editBankMovement', 'updateBankMovement', 'conciliateBankMovement', 'linkBankMovement', 'createExpenseFromBankMovement', 'createExpenseFromBankMovementRoute', 'linkBankMovementToExpense', 'linkBankMovementToIncome', 'conciliateAsTransfer', 'ignoreBankMovement', 'ignoreBankMovementRoute', 'confirmTransfer', 'linkExpenseFromBankMovement']);
        $this->middleware('permission:treasury.bank_conciliation.delete')->only(['destroyBankMovement', 'destroyBulkBankMovements']);
        $this->middleware('permission:treasury.cash_control.view')->only(['createCashWithdrawal', 'storeCashWithdrawal']);
        $this->middleware('permission:treasury.cash_wallets.create')->only(['storeCashDeposit']);
    }

    public function index(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = FinancialEntry::with(['store', 'creator']);
        $storeParam = $request->get('store', 'all');
        $storeIdForScope = ($storeParam !== 'all' && $storeParam !== '') ? (int) $storeParam : null;
        $this->scopeStoreForCurrentUser($query, 'store_id', $storeIdForScope);

        if ($storeParam !== 'all' && $storeParam !== '' && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())) {
            $query->where('store_id', $storeParam);
        }

        $period = $request->get('period', 'last_30');
        $this->applyPeriodFilter($query, $period, $request);

        // Filtro por tipo
        if ($request->filled('type') && $request->get('type') !== 'all') {
            $query->where('type', $request->get('type'));
        }

        // Filtro por categoría (gastos o ingresos)
        if ($request->filled('category')) {
            $query->where(function ($q) use ($request) {
                $q->where('expense_category', $request->get('category'))
                    ->orWhere('income_category', $request->get('category'));
            });
        }

        // Filtro por usuario creador
        if ($request->filled('user')) {
            $query->where('created_by', $request->get('user'));
        }

        // Búsqueda por concepto o notas
        if ($request->filled('search')) {
            $term = '%'.$request->get('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('concept', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhere('expense_concept', 'like', $term)
                    ->orWhere('income_concept', 'like', $term);
            });
        }

        $entries = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->paginate(50);
        $stores = $this->storesForCurrentUser();
        $companyId = session('company_id');
        $users = $companyId
            ? User::where('company_id', $companyId)->orWhereNull('company_id')->orderBy('name')->get()
            : User::orderBy('name')->get();

        return view('financial.index', compact('entries', 'stores', 'period', 'users'));
    }

    public function create(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $allowedTypes = [];
        if (auth()->user()->hasPermission('financial.daily_closes.create')) {
            $allowedTypes[] = 'daily_close';
        }
        if (auth()->user()->hasPermission('financial.income.create')) {
            $allowedTypes[] = 'income';
        }
        if (auth()->user()->hasPermission('financial.expenses.create')) {
            $allowedTypes[] = 'expense';
        }
        if (empty($allowedTypes)) {
            abort(403, 'No tienes permiso para crear registros en este módulo.');
        }

        $type = $request->get('type', 'daily_close');
        // Si se llega con ?type=... (ej. desde "Añadir cierre diario"), restringir a ese tipo
        // para abrir directamente el formulario correspondiente sin elegir tipo.
        if ($request->has('type') && in_array($request->type, $allowedTypes, true)) {
            $allowedTypes = [$request->type];
        }

        $stores = $this->getAvailableStores();
        $suppliers = Supplier::orderBy('name')->get();
        $dailyCloseSettings = $this->getDailyCloseSettings();
        $expenseCategories = \App\Models\ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        try {
            return view('financial.create', compact('stores', 'suppliers', 'type', 'allowedTypes', 'dailyCloseSettings', 'expenseCategories'));
        } catch (\Exception $e) {
            Log::error('Error en FinancialController@create: '.$e->getMessage());

            return redirect()->route('financial.index')->with('error', 'Error al cargar el formulario');
        }
    }

    public function store(Request $request)
    {
        // Validación base (gastos permiten importe negativo)
        $rules = [
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'reporting_month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'type' => 'required|in:income,expense,daily_close',
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|in:pendiente,pagado',
            'expense_payments' => 'nullable|array',
            'expense_payments.*.date' => 'required|date',
            'expense_payments.*.method' => 'required|in:cash,bank',
            'expense_payments.*.amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'expense_category' => 'nullable|string',
            'expense_concept' => 'nullable|string',
            'expense_payment_method' => 'nullable|in:cash,bank,card,datafono,tarjeta',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'income_category' => 'nullable|string',
            'income_concept' => 'nullable|string',
            'income_amount' => 'nullable|numeric|min:0',
            'income_payment_method' => 'nullable|in:cash,bank,card,datafono,tarjeta',
        ];

        // Si es cierre diario, agregar validaciones específicas
        if ($request->input('type') === 'daily_close') {
            $rules['cash_initial'] = 'required|numeric|min:0';
            $rules['tpv'] = 'required|numeric|min:0';
            $rules['cash_expenses'] = 'nullable|numeric';
            $rules['cash_count'] = 'nullable|array';
            $rules['shopify_cash'] = 'nullable|numeric|min:0';
            $rules['shopify_tpv'] = 'nullable|numeric|min:0';
            $rules['vouchers_in'] = 'nullable|numeric|min:0';
            $rules['vouchers_out'] = 'nullable|numeric|min:0';
            $rules['vouchers_result'] = 'nullable|numeric';
            $rules['expense_items'] = 'nullable|array';
            $rules['expense_items.*.concept'] = 'nullable|string';
            $rules['expense_items.*.expense_category'] = 'nullable|string';
            $rules['expense_items.*.amount'] = 'nullable|numeric';
        } else {
            // Campos opcionales para otros tipos
            $rules['cash_initial'] = 'nullable|numeric|min:0';
            $rules['tpv'] = 'nullable|numeric|min:0';
            $rules['cash_expenses'] = 'nullable|numeric';
            $rules['cash_count'] = 'nullable|array';
            $rules['shopify_cash'] = 'nullable|numeric|min:0';
            $rules['shopify_tpv'] = 'nullable|numeric|min:0';
            $rules['vouchers_in'] = 'nullable|numeric|min:0';
            $rules['vouchers_out'] = 'nullable|numeric|min:0';
            $rules['vouchers_result'] = 'nullable|numeric';
            $rules['expense_items'] = 'nullable|array';
            $rules['expense_items.*.concept'] = 'nullable|string';
            $rules['expense_items.*.expense_category'] = 'nullable|string';
            $rules['expense_items.*.amount'] = 'nullable|numeric';
        }

        $validated = $request->validate($rules);

        $validated['store_id'] = $this->enforcedStoreIdForCreate((int) ($validated['store_id'] ?? 0) ?: null);
        if (! $validated['store_id']) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Debe seleccionar una tienda.']);
        }

        // Normalizar valores vacíos a 0 para campos numéricos requeridos de cierre diario
        if ($validated['type'] === 'daily_close') {
            if ($request->input('cash_initial') === '' || $request->input('cash_initial') === null) {
                $validated['cash_initial'] = 0;
            }
            if ($request->input('tpv') === '' || $request->input('tpv') === null) {
                $validated['tpv'] = 0;
            }
        }

        $validated = $request->validate($rules);

        // Un solo cierre diario por tienda y fecha (ese cierre genera un ingreso de efectivo y uno de datáfono)
        if ($validated['type'] === 'daily_close') {
            $exists = FinancialEntry::where('type', 'daily_close')
                ->where('store_id', $validated['store_id'])
                ->whereDate('date', $validated['date'])
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'date' => ['Ya existe un cierre diario para esta tienda y fecha. Solo puede haber un cierre por tienda y día.'],
                ]);
            }
        }

        try {
            $date = \Carbon\Carbon::parse($validated['date']);
            $defaultMonth = $date->format('Y-m');

            // Preparar datos para crear
            $entryData = [
                'date' => $validated['date'],
                'store_id' => $validated['store_id'],
                'type' => $validated['type'],
                'created_by' => Auth::id(),
            ];

            if ($validated['type'] === 'daily_close') {
                $entryData['reporting_month'] = $defaultMonth;
            } else {
                $entryData['reporting_month'] = $validated['reporting_month'] ?? $defaultMonth;
            }

            // Agregar notas si existen
            if (isset($validated['notes'])) {
                $entryData['notes'] = $validated['notes'];
            }

            // Si es un cierre diario, procesar campos específicos
            if ($validated['type'] === 'daily_close') {
                $cashInitial = (float) ($validated['cash_initial'] ?? $request->input('cash_initial', 0));
                $tpv = (float) ($validated['tpv'] ?? $request->input('tpv', 0));
                $cashExpenses = (float) ($validated['cash_expenses'] ?? $request->input('cash_expenses', 0));
                $cashCount = $validated['cash_count'] ?? $request->input('cash_count', []);
                $shopifyCash = isset($validated['shopify_cash']) && $validated['shopify_cash'] !== '' ? (float) $validated['shopify_cash'] : ($request->has('shopify_cash') && $request->input('shopify_cash') !== '' ? (float) $request->input('shopify_cash') : null);
                $shopifyTpv = isset($validated['shopify_tpv']) && $validated['shopify_tpv'] !== '' ? (float) $validated['shopify_tpv'] : ($request->has('shopify_tpv') && $request->input('shopify_tpv') !== '' ? (float) $request->input('shopify_tpv') : null);
                $vouchersIn = (float) ($validated['vouchers_in'] ?? $request->input('vouchers_in', 0));
                $vouchersOut = (float) ($validated['vouchers_out'] ?? $request->input('vouchers_out', 0));
                $vouchersResult = isset($validated['vouchers_result']) ? (float) $validated['vouchers_result'] : ($vouchersIn - $vouchersOut);
                $expenseItems = $validated['expense_items'] ?? $request->input('expense_items', []);

                // Calcular efectivo contado desde cash_count
                $cashCounted = 0;
                if (is_array($cashCount)) {
                    foreach ($cashCount as $denomination => $count) {
                        $cashCounted += (float) $denomination * (int) $count;
                    }
                }

                // Calcular ventas en efectivo
                $computedCashSales = round($cashCounted - $cashInitial + $cashExpenses, 2);

                // Calcular ventas totales
                $totalSales = round($tpv + $computedCashSales + $vouchersResult, 2);

                // Calcular gastos totales desde expense_items
                $totalExpenses = 0;
                if (is_array($expenseItems)) {
                    foreach ($expenseItems as $item) {
                        $totalExpenses += (float) ($item['amount'] ?? 0);
                    }
                }

                $entryData['cash_initial'] = round($cashInitial, 2);
                $entryData['tpv'] = round($tpv, 2);
                $entryData['cash_expenses'] = round($cashExpenses, 2);
                $entryData['cash_count'] = $cashCount;
                $entryData['shopify_cash'] = $shopifyCash !== null ? round($shopifyCash, 2) : null;
                $entryData['shopify_tpv'] = $shopifyTpv !== null ? round($shopifyTpv, 2) : null;
                $entryData['vouchers_in'] = round($vouchersIn, 2);
                $entryData['vouchers_out'] = round($vouchersOut, 2);
                $entryData['vouchers_result'] = round($vouchersResult, 2);
                $entryData['expense_items'] = $expenseItems;
                $entryData['sales'] = $totalSales;
                $entryData['expenses'] = round($totalExpenses, 2);
                $entryData['amount'] = $totalSales;
            }

            // Si es un ingreso, procesar campos específicos
            if ($validated['type'] === 'income') {
                $entryData['income_amount'] = $validated['total_amount'] ?? $validated['income_amount'] ?? $request->input('income_amount', 0);
                $entryData['amount'] = $validated['total_amount'] ?? $validated['income_amount'] ?? $request->input('income_amount', 0);
                $entryData['total_amount'] = $validated['total_amount'] ?? $validated['income_amount'] ?? $request->input('income_amount', 0);

                // Guardar categoría y concepto del ingreso
                if (isset($validated['income_category'])) {
                    $entryData['income_category'] = $validated['income_category'];
                }
                if (isset($validated['income_concept'])) {
                    $entryData['income_concept'] = $validated['income_concept'];
                    $entryData['concept'] = $validated['income_concept'];
                }

                // Normalizar método de pago: datáfono, tarjeta o card se guardan como bank
                if (isset($validated['income_payment_method'])) {
                    $paymentMethod = $validated['income_payment_method'];
                    if (in_array($paymentMethod, ['card', 'datafono', 'tarjeta'])) {
                        $entryData['expense_payment_method'] = 'bank';
                    } else {
                        $entryData['expense_payment_method'] = $paymentMethod;
                    }
                }
            }

            // Si es un gasto, procesar campos específicos
            if ($validated['type'] === 'expense') {
                $entryData['expense_amount'] = $validated['total_amount'] ?? $request->input('expense_amount', 0);
                $entryData['amount'] = $validated['total_amount'] ?? $request->input('expense_amount', 0);
                $entryData['total_amount'] = $validated['total_amount'] ?? $request->input('expense_amount', 0);
                $entryData['status'] = $validated['status'] ?? 'pendiente';
                $entryData['expense_source'] = 'gasto_manual';

                // Guardar categoría, concepto y método de pago del gasto
                if (isset($validated['expense_category'])) {
                    $entryData['expense_category'] = $validated['expense_category'];
                }
                if (isset($validated['expense_concept'])) {
                    $entryData['expense_concept'] = $validated['expense_concept'];
                }
                // ENUM solo permite cash,bank,transfer,card; vacío = null; datafono/tarjeta → card
                $paymentMethod = $validated['expense_payment_method'] ?? null;
                if ($paymentMethod !== null && $paymentMethod !== '') {
                    $entryData['expense_payment_method'] = in_array($paymentMethod, ['datafono', 'tarjeta'], true) ? 'card' : $paymentMethod;
                } else {
                    $entryData['expense_payment_method'] = null;
                }
                if (isset($validated['supplier_id']) && $validated['supplier_id']) {
                    $entryData['supplier_id'] = $validated['supplier_id'];
                }

                // Calcular paid_amount desde los pagos
                if ($request->has('expense_payments')) {
                    $totalPaid = 0;
                    foreach ($request->expense_payments as $payment) {
                        $totalPaid += (float) ($payment['amount'] ?? 0);
                    }
                    $entryData['paid_amount'] = $totalPaid;

                    // Actualizar status según pagos
                    if ($totalPaid >= $entryData['total_amount'] && $entryData['total_amount'] > 0) {
                        $entryData['status'] = 'pagado';
                    }
                } else {
                    // Si el gasto se marca como pagado y no se detallan pagos, considerar pagado el total
                    $totalAmount = (float) ($entryData['total_amount'] ?? 0);
                    $entryData['paid_amount'] = ($entryData['status'] ?? 'pendiente') === 'pagado'
                        ? $totalAmount
                        : 0;
                }
            }

            Log::info('Intentando crear registro financiero', [
                'type' => $validated['type'],
                'store_id' => $validated['store_id'],
                'date' => $validated['date'],
                'entryData_keys' => array_keys($entryData),
            ]);

            $entry = FinancialEntry::create($entryData);

            Log::info('Registro financiero creado exitosamente', ['entry_id' => $entry->id]);

            // Crear pagos si es un gasto
            if ($validated['type'] === 'expense' && $request->has('expense_payments')) {
                try {
                    if (Schema::hasTable('expense_payments')) {
                        foreach ($request->expense_payments as $payment) {
                            $entry->expensePayments()->create([
                                'date' => $payment['date'],
                                'method' => $payment['method'],
                                'amount' => (float) $payment['amount'],
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    // La tabla aún no existe, continuar sin crear pagos
                    Log::warning('No se pudieron crear los pagos del gasto: '.$e->getMessage());
                }
            }

            // Lógica adicional según el tipo...
            if ($validated['type'] === 'daily_close') {
                // Crear ingresos automáticamente para efectivo y datáfono
                $this->syncDailyCloseIncomes($entry);
                // Crear registros de gasto por cada expense_item para que aparezcan en el apartado de gastos
                $this->syncDailyCloseExpenses($entry);
            }

            // Redirigir a la lista correspondiente al tipo de registro (mantenerse en la misma sección)
            if ($entry->type === 'daily_close') {
                return redirect()->route('financial.daily-closes')->with('success', 'Cierre diario creado correctamente.');
            }
            if ($entry->type === 'income') {
                return redirect()->route('financial.income')->with('success', 'Ingreso creado correctamente.');
            }
            if ($entry->type === 'expense') {
                return redirect()->route('financial.expenses')->with('success', 'Gasto creado correctamente.');
            }

            return redirect()->route('financial.show', $entry)->with('success', 'Registro creado correctamente');
        } catch (ValidationException $e) {
            Log::warning('Error de validación al crear registro', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creando registro financiero', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token', 'password']),
            ]);

            return back()->withInput()->with('error', 'Error al crear el registro: '.$e->getMessage());
        }
    }

    public function show($id)
    {
        $entry = FinancialEntry::with(['bankMovements.bankAccount', 'tpvDifferenceExpense'])->findOrFail($id);
        $this->authorizeStoreAccess($entry->store_id);
        $canView = $entry->type === 'daily_close'
            ? auth()->user()->hasPermission('financial.daily_closes.view')
            : auth()->user()->hasPermission('financial.registros.view');
        if (! $canView) {
            abort(403, 'No tienes permisos para ver esta acción.');
        }
        $dailyCloseSettings = $this->getDailyCloseSettings($entry->store?->company_id);
        $expenseCategories = \App\Models\ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        $linkedMovementsSum = 0;
        $canCreateTpvDifference = false;
        $tpvDifferenceAmount = 0;
        if ($entry->type === 'income' && ($entry->expense_payment_method ?? '') === 'bank') {
            $linkedMovementsSum = (float) $entry->bankMovements->sum('amount');
            $incomeAmount = (float) ($entry->total_amount ?? $entry->amount ?? $entry->income_amount ?? 0);
            $tpvDifferenceAmount = round($incomeAmount - $linkedMovementsSum, 2);
            $hasTpvDifferenceExpense = $entry->tpvDifferenceExpense()->exists();
            $canCreateTpvDifference = $tpvDifferenceAmount > 0 && ! $hasTpvDifferenceExpense && auth()->user()->hasPermission('financial.expenses.create');
        }

        return view('financial.show', compact('entry', 'dailyCloseSettings', 'expenseCategories', 'linkedMovementsSum', 'canCreateTpvDifference', 'tpvDifferenceAmount'));
    }

    public function edit($id)
    {
        $this->syncStoresFromBusinesses();
        $entry = FinancialEntry::findOrFail($id);

        // Intentar cargar pagos si la tabla existe
        try {
            if (\Schema::hasTable('expense_payments')) {
                $entry->load('expensePayments');
            } else {
                // Si la tabla no existe, inicializar como colección vacía para evitar errores en la vista
                $entry->setRelation('expensePayments', collect());
            }
        } catch (\Exception $e) {
            // Continuar sin cargar pagos
            $entry->setRelation('expensePayments', collect());
        }

        $stores = $this->getAvailableStores();
        $dailyCloseSettings = $this->getDailyCloseSettings();
        $expenseCategories = \App\Models\ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('financial.edit', compact('entry', 'stores', 'dailyCloseSettings', 'expenseCategories'));
    }

    public function update(Request $request, $id)
    {
        $entry = FinancialEntry::findOrFail($id);
        $canEdit = $entry->type === 'daily_close'
            ? auth()->user()->hasPermission('financial.daily_closes.edit')
            : auth()->user()->hasPermission('financial.registros.edit');
        if (! $canEdit) {
            abort(403, 'No tienes permisos para editar esta acción.');
        }

        $rules = [
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'reporting_month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'notes' => 'nullable|string|max:65535',
            'status' => 'nullable|in:pendiente,pagado',
            'expense_category' => 'nullable|string',
            'expense_concept' => 'nullable|string',
            'expense_payment_method' => 'nullable|in:cash,bank,card,datafono,tarjeta',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'income_category' => 'nullable|string',
            'income_concept' => 'nullable|string',
            'income_payment_method' => 'nullable|in:cash,bank,card,datafono,tarjeta',
        ];
        // Cierre diario: total_amount y amount pueden ser negativos; no validar expense_payments
        if ($entry->type === 'daily_close') {
            $rules['total_amount'] = 'nullable|numeric';
            $rules['income_amount'] = 'nullable|numeric';
        } else {
            // Gastos permiten importe negativo; ingresos mantienen min:0
            $rules['total_amount'] = 'nullable|numeric';
            $rules['income_amount'] = 'nullable|numeric|min:0';
            $rules['expense_payments'] = 'nullable|array';
            $rules['expense_payments.*.date'] = 'required|date';
            $rules['expense_payments.*.method'] = 'required|in:cash,bank';
            $rules['expense_payments.*.amount'] = 'required|numeric';
        }
        $validated = $request->validate($rules);

        // Un solo cierre diario por tienda y fecha (al editar, no puede haber otro cierre con la misma tienda y fecha)
        if ($entry->type === 'daily_close') {
            $exists = FinancialEntry::where('type', 'daily_close')
                ->where('store_id', $validated['store_id'])
                ->whereDate('date', $validated['date'])
                ->where('id', '!=', $entry->id)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'date' => ['Ya existe un cierre diario para esta tienda y fecha. Solo puede haber un cierre por tienda y día.'],
                ]);
            }
        }

        try {
            // Si es un cierre diario, procesar campos específicos
            if ($entry->type === 'daily_close') {
                $cashInitial = (float) ($request->input('cash_initial', $entry->cash_initial ?? 0));
                $tpv = (float) ($request->input('tpv', $entry->tpv ?? 0));
                $cashExpenses = (float) ($request->input('cash_expenses', $entry->cash_expenses ?? 0));
                $cashCount = $request->input('cash_count', $entry->cash_count ?? []);
                $shopifyCash = $request->has('shopify_cash') && $request->input('shopify_cash') !== '' ? (float) $request->input('shopify_cash') : ($entry->shopify_cash ?? null);
                $shopifyTpv = $request->has('shopify_tpv') && $request->input('shopify_tpv') !== '' ? (float) $request->input('shopify_tpv') : ($entry->shopify_tpv ?? null);
                $vouchersIn = (float) ($request->input('vouchers_in', $entry->vouchers_in ?? 0));
                $vouchersOut = (float) ($request->input('vouchers_out', $entry->vouchers_out ?? 0));
                $vouchersResult = $request->has('vouchers_result') ? (float) $request->input('vouchers_result') : ($vouchersIn - $vouchersOut);
                $expenseItems = $request->input('expense_items', $entry->expense_items ?? []);

                // Calcular efectivo contado desde cash_count
                $cashCounted = 0;
                if (is_array($cashCount)) {
                    foreach ($cashCount as $denomination => $count) {
                        $cashCounted += (float) $denomination * (int) $count;
                    }
                }

                // Calcular ventas en efectivo
                $computedCashSales = round($cashCounted - $cashInitial + $cashExpenses, 2);

                // Calcular ventas totales
                $totalSales = round($tpv + $computedCashSales + $vouchersResult, 2);

                // Calcular gastos totales desde expense_items
                $totalExpenses = 0;
                if (is_array($expenseItems)) {
                    foreach ($expenseItems as $item) {
                        $totalExpenses += (float) ($item['amount'] ?? 0);
                    }
                }

                $validated['cash_initial'] = round($cashInitial, 2);
                $validated['tpv'] = round($tpv, 2);
                $validated['cash_expenses'] = round($cashExpenses, 2);
                $validated['cash_count'] = $cashCount;
                $validated['shopify_cash'] = $shopifyCash !== null ? round($shopifyCash, 2) : null;
                $validated['shopify_tpv'] = $shopifyTpv !== null ? round($shopifyTpv, 2) : null;
                $validated['vouchers_in'] = round($vouchersIn, 2);
                $validated['vouchers_out'] = round($vouchersOut, 2);
                $validated['vouchers_result'] = round($vouchersResult, 2);
                $validated['expense_items'] = $expenseItems;
                $validated['sales'] = $totalSales;
                $validated['expenses'] = round($totalExpenses, 2);
                $validated['amount'] = $totalSales;
                $validated['total_amount'] = $totalSales; // Objetivos mensuales leen total_amount; debe actualizarse al editar el cierre
                $validated['reporting_month'] = \Carbon\Carbon::parse($validated['date'])->format('Y-m');
            }

            // Si es un ingreso o gasto, mes correspondiente editable
            if (in_array($entry->type, ['income', 'expense', 'expense_refund'])) {
                $validated['reporting_month'] = $validated['reporting_month'] ?? \Carbon\Carbon::parse($validated['date'])->format('Y-m');
            }

            // Si es un ingreso, procesar campos específicos
            if ($entry->type === 'income') {
                if ($request->has('total_amount')) {
                    $validated['income_amount'] = $validated['total_amount'];
                    $validated['amount'] = $validated['total_amount'];
                } elseif ($request->has('income_amount')) {
                    $validated['income_amount'] = $validated['income_amount'];
                    $validated['amount'] = $validated['income_amount'];
                }

                // Guardar categoría y concepto del ingreso
                if ($request->has('income_category')) {
                    $validated['income_category'] = $request->input('income_category');
                }
                if ($request->has('income_concept')) {
                    $validated['income_concept'] = $request->input('income_concept');
                    $validated['concept'] = $request->input('income_concept');
                }

                // Normalizar método de pago: datáfono, tarjeta o card se guardan como bank
                if ($request->has('income_payment_method')) {
                    $paymentMethod = $request->input('income_payment_method');
                    if (in_array($paymentMethod, ['card', 'datafono', 'tarjeta'])) {
                        $validated['expense_payment_method'] = 'bank';
                    } else {
                        $validated['expense_payment_method'] = $paymentMethod;
                    }
                }
            }

            // Si es un gasto, procesar campos específicos
            if ($entry->type === 'expense') {
                if ($request->has('total_amount')) {
                    $validated['expense_amount'] = $validated['total_amount'];
                    $validated['amount'] = $validated['total_amount'];
                }

                // Guardar categoría, concepto y método de pago del gasto
                if ($request->has('expense_category')) {
                    $validated['expense_category'] = $request->input('expense_category');
                }
                if ($request->has('expense_concept')) {
                    $validated['expense_concept'] = $request->input('expense_concept');
                }
                if ($request->has('expense_payment_method')) {
                    $pm = $request->input('expense_payment_method');
                    $validated['expense_payment_method'] = ($pm === '' || $pm === null) ? null : (in_array($pm, ['datafono', 'tarjeta'], true) ? 'card' : $pm);
                }

                // Calcular paid_amount desde los pagos
                if ($request->has('expense_payments')) {
                    $totalPaid = 0;
                    foreach ($request->expense_payments as $payment) {
                        $totalPaid += (float) ($payment['amount'] ?? 0);
                    }
                    $validated['paid_amount'] = $totalPaid;

                    // Actualizar status según pagos
                    $totalAmount = $validated['total_amount'] ?? $entry->total_amount ?? $entry->amount ?? 0;
                    if ($totalPaid >= $totalAmount && $totalAmount > 0) {
                        $validated['status'] = 'pagado';
                    } else {
                        $validated['status'] = $validated['status'] ?? 'pendiente';
                    }
                }

                // Si se marca como pagado y no se detallan pagos, considerar pagado el total (corrige gastos antiguos)
                if (! $request->has('expense_payments') && $request->has('status')) {
                    $totalAmount = (float) ($validated['total_amount'] ?? $entry->total_amount ?? $entry->amount ?? 0);
                    if (($validated['status'] ?? null) === 'pagado') {
                        $validated['paid_amount'] = $totalAmount;
                    } elseif (($validated['status'] ?? null) === 'pendiente') {
                        $validated['paid_amount'] = 0;
                    }
                }
            }

            // Solo pasar atributos que el modelo puede asignar (evita errores si el request trae campos extra)
            $fillable = (new FinancialEntry)->getFillable();
            $dataToUpdate = array_intersect_key($validated, array_flip($fillable));
            // MySQL ENUM y columnas nullable no aceptan ''; convertir a null para evitar "Data truncated"
            $nullableStringFields = [
                'concept', 'income_category', 'income_concept', 'expense_category', 'expense_payment_method',
                'expense_concept', 'expense_source', 'notes', 'refund_concept', 'refund_type',
            ];
            foreach ($nullableStringFields as $key) {
                if (array_key_exists($key, $dataToUpdate) && $dataToUpdate[$key] === '') {
                    $dataToUpdate[$key] = null;
                }
            }

            // Campos enteros nullable (p. ej. selects con opción vacía) pueden llegar como '' desde HTML.
            // MySQL no acepta '' en columnas INT: convertir a null.
            $nullableIntegerFields = ['supplier_id', 'invoice_id', 'source_income_id', 'refund_original_id'];
            foreach ($nullableIntegerFields as $key) {
                if (array_key_exists($key, $dataToUpdate) && ($dataToUpdate[$key] === '' || $dataToUpdate[$key] === null)) {
                    $dataToUpdate[$key] = null;
                }
            }

            $entry->update($dataToUpdate);
            $entry->refresh();

            // Sincronizar ingresos automáticos y gastos del cierre si es un cierre diario
            if ($entry->type === 'daily_close') {
                $this->syncDailyCloseIncomes($entry);
                $this->syncDailyCloseExpenses($entry);
            }

            // Actualizar pagos si es un gasto
            if ($entry->type === 'expense' && $request->has('expense_payments')) {
                try {
                    if (Schema::hasTable('expense_payments')) {
                        // Eliminar pagos existentes
                        $entry->expensePayments()->delete();

                        // Crear nuevos pagos
                        foreach ($request->expense_payments as $payment) {
                            $entry->expensePayments()->create([
                                'date' => $payment['date'],
                                'method' => $payment['method'],
                                'amount' => (float) $payment['amount'],
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    // La tabla aún no existe, continuar sin actualizar pagos
                    Log::warning('No se pudieron actualizar los pagos del gasto: '.$e->getMessage());
                }
            }

            // Redirigir a la lista correspondiente al tipo de registro (mantenerse en la misma sección)
            $redirectTo = $request->input('redirect_to');
            if ($redirectTo && is_string($redirectTo)) {
                $host = parse_url($redirectTo, PHP_URL_HOST);
                if (($host === null && str_starts_with($redirectTo, '/')) || ($host !== null && $host === $request->getHost())) {
                    return redirect($redirectTo)->with('success', 'Registro actualizado correctamente.');
                }
            }

            if ($entry->type === 'daily_close') {
                return redirect()->route('financial.daily-closes')->with('success', 'Cierre diario actualizado correctamente.');
            }
            if ($entry->type === 'income') {
                return redirect()->route('financial.income')->with('success', 'Ingreso actualizado correctamente.');
            }
            if ($entry->type === 'expense') {
                return redirect()->route('financial.expenses')->with('success', 'Gasto actualizado correctamente.');
            }

            return redirect()->route('financial.show', $entry)->with('success', 'Registro actualizado correctamente');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error actualizando registro financiero', [
                'entry_id' => $id ?? null,
                'type' => $entry->type ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Error al actualizar el registro. Revisa el log para más detalles.');
        }
    }

    /**
     * Actualiza solo la categoría de un gasto (para edición inline en la lista de gastos).
     */
    public function updateExpenseCategory(Request $request, $entry)
    {
        $entry = FinancialEntry::findOrFail($entry);
        if ($entry->type !== 'expense') {
            abort(404);
        }
        if (! auth()->user()->hasPermission('financial.expenses.edit') && ! auth()->user()->hasPermission('financial.registros.edit')) {
            abort(403, 'No tienes permiso para editar gastos.');
        }
        $this->authorizeStoreAccess($entry->store_id);

        $validated = $request->validate([
            'expense_category' => 'nullable|string|max:255',
        ]);
        $value = $validated['expense_category'] ?? '';
        $entry->update(['expense_category' => $value ?: null]);

        $label = $value ? ucfirst(str_replace('_', ' ', $value)) : '—';

        return response()->json([
            'success' => true,
            'expense_category' => $value,
            'label' => $label,
        ]);
    }

    /**
     * Actualiza solo el mes correspondiente (para edición inline en ingresos y gastos).
     */
    public function updateReportingMonth(Request $request, $entry)
    {
        $entry = FinancialEntry::findOrFail($entry);
        if (! in_array($entry->type, ['income', 'expense', 'expense_refund'])) {
            abort(404);
        }
        if ($entry->type === 'expense' && ($entry->expense_source ?? '') === 'cierre_diario') {
            abort(403, 'El mes correspondiente de gastos de cierre diario no es editable.');
        }
        if (! auth()->user()->hasPermission('financial.registros.edit')
            && ! ($entry->type === 'income' && auth()->user()->hasPermission('financial.income.edit'))
            && ! ($entry->type === 'expense' && auth()->user()->hasPermission('financial.expenses.edit'))) {
            abort(403, 'No tienes permiso para editar este registro.');
        }
        $this->authorizeStoreAccess($entry->store_id);

        $validated = $request->validate([
            'reporting_month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
        ]);
        $value = $validated['reporting_month'] ?? null;
        $entry->update(['reporting_month' => $value]);

        $label = $value ? \Carbon\Carbon::createFromFormat('!Y-m', $value)->locale('es')->isoFormat('MMMM YYYY') : '—';

        return response()->json([
            'success' => true,
            'reporting_month' => $value,
            'label' => ucfirst($label),
        ]);
    }

    public function destroy($id)
    {
        try {
            $entry = FinancialEntry::findOrFail($id);
            $this->authorizeStoreAccess($entry->store_id);

            // Permiso según tipo: cierres diarios, ingresos o gastos pueden tener permiso específico
            $canDelete = match ($entry->type) {
                'daily_close' => auth()->user()->hasPermission('financial.daily_closes.delete') || auth()->user()->hasPermission('financial.registros.delete'),
                'income' => auth()->user()->hasPermission('financial.income.delete') || auth()->user()->hasPermission('financial.registros.delete'),
                'expense' => auth()->user()->hasPermission('financial.expenses.delete') || auth()->user()->hasPermission('financial.registros.delete'),
                default => auth()->user()->hasPermission('financial.registros.delete'),
            };
            if (! $canDelete) {
                abort(403, 'No tienes permiso para eliminar este registro.');
            }

            // Eliminar ingresos y gastos automáticos si es un cierre diario
            if ($entry->type === 'daily_close') {
                $this->deleteDailyCloseIncomes($entry);
                $this->deleteDailyCloseExpenses($entry);
            }

            // Eliminar pagos relacionados si es un gasto (evitar registros huérfanos en todas las vistas)
            if ($entry->type === 'expense') {
                try {
                    if (Schema::hasTable('expense_payments')) {
                        $entry->expensePayments()->delete();
                    }
                } catch (\Exception $e) {
                    Log::warning('No se pudieron eliminar los pagos del gasto: '.$e->getMessage());
                }

                // Si el gasto proviene de un pedido, eliminar el pago del pedido para que desaparezca también en órdenes
                $notes = is_string($entry->notes) ? json_decode($entry->notes, true) : $entry->notes;
                if (is_array($notes) && ! empty($notes['order_payment_id'])) {
                    $orderPayment = OrderPayment::find($notes['order_payment_id']);
                    if ($orderPayment) {
                        $orderPayment->delete();
                        Log::info('OrderPayment eliminado al borrar gasto desde módulo gastos', ['order_payment_id' => $orderPayment->id]);
                    }
                }

                // Eliminar registro de cash_wallet_expenses si existe (para que desaparezca del historial de la cartera)
                try {
                    if (Schema::hasTable('cash_wallet_expenses')) {
                        CashWalletExpense::where('financial_entry_id', $entry->id)->delete();
                        Log::info('Registro de cash_wallet_expenses eliminado', ['financial_entry_id' => $entry->id]);
                    }
                } catch (\Exception $e) {
                    Log::warning('No se pudo eliminar el registro de cash_wallet_expenses: '.$e->getMessage());
                }
            }

            $entry->delete();

            // Si se envió redirect_to (ej. desde control de efectivo), redirigir allí si es URL segura
            $redirectTo = request()->input('redirect_to');
            if ($redirectTo && is_string($redirectTo)) {
                $host = parse_url($redirectTo, PHP_URL_HOST);
                if ($host === null || $host === request()->getHost()) {
                    return redirect($redirectTo)->with('success', $entry->type === 'expense' ? 'Gasto eliminado correctamente.' : ($entry->type === 'income' ? 'Ingreso eliminado correctamente.' : 'Registro eliminado correctamente.'));
                }
            }

            // Redirigir a la lista correspondiente al tipo (mantenerse en la misma sección)
            if ($entry->type === 'daily_close') {
                return redirect()->route('financial.daily-closes')->with('success', 'Cierre diario eliminado correctamente.');
            }
            if ($entry->type === 'income') {
                return redirect()->route('financial.income')->with('success', 'Ingreso eliminado correctamente.');
            }
            if ($entry->type === 'expense') {
                return redirect()->route('financial.expenses')->with('success', 'Gasto eliminado correctamente.');
            }

            return redirect()->route('financial.index')->with('success', 'Registro eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando registro: '.$e->getMessage());

            return back()->with('error', 'Error al eliminar el registro');
        }
    }

    public function income(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = FinancialEntry::with(['store', 'creator'])
            ->where('type', 'income');

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($request->has('store') && $request->store !== 'all') {
            $query->where('store_id', $request->store);
        }

        $period = $request->get('period', 'last_30');
        $month = $request->get('month');
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $query->whereRaw("COALESCE(reporting_month, DATE_FORMAT(date, '%Y-%m')) = ?", [$month]);
            } else {
                $query->whereRaw("COALESCE(reporting_month, strftime('%Y-%m', date)) = ?", [$month]);
            }
        } else {
            $this->applyPeriodFilter($query, $period, $request);
        }

        // Filtro por categoría
        if ($request->has('category') && $request->category) {
            $query->where('income_category', $request->category);
        }

        // Filtro por método de pago
        if ($request->has('payment_method') && $request->payment_method) {
            if ($request->payment_method === 'cash') {
                $query->where('expense_payment_method', 'cash');
            } elseif ($request->payment_method === 'bank') {
                // Banco incluye: bank, card, datafono, tarjeta
                $query->whereIn('expense_payment_method', ['bank', 'card', 'datafono', 'tarjeta']);
            }
        }

        // Filtro por procedencia (income_category)
        if ($request->filled('source')) {
            $query->where('income_category', $request->source);
        }

        $entries = $query->orderBy('date', 'desc')->get();
        $stores = $this->getAvailableStores();

        return view('financial.income', compact('entries', 'stores', 'period'));
    }

    public function expenses(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = FinancialEntry::with(['store', 'creator', 'invoice'])
            ->where('type', 'expense');

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($request->has('store') && $request->store !== 'all') {
            $query->where('store_id', $request->store);
        }

        $period = $request->get('period', 'last_30');
        $month = $request->get('month');
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $query->whereRaw("COALESCE(reporting_month, DATE_FORMAT(date, '%Y-%m')) = ?", [$month]);
            } else {
                $query->whereRaw("COALESCE(reporting_month, strftime('%Y-%m', date)) = ?", [$month]);
            }
        } else {
            $this->applyPeriodFilter($query, $period, $request);
        }

        // Filtro por categoría
        if ($request->has('category') && $request->category) {
            $query->where('expense_category', $request->category);
        }

        // Filtro por método de pago
        if ($request->filled('payment_method')) {
            if ($request->payment_method === 'cash') {
                $query->where('expense_payment_method', 'cash');
            } elseif ($request->payment_method === 'bank') {
                $query->whereIn('expense_payment_method', ['bank', 'card', 'datafono', 'tarjeta']);
            }
        }

        // Filtro "Solo pendientes"
        if ($request->has('only_pending') && $request->only_pending == '1') {
            $query->whereRaw('(COALESCE(total_amount, 0) - COALESCE(paid_amount, 0)) > 0');
        }

        // Filtro por procedencia (expense_source)
        if ($request->filled('source')) {
            $query->where('expense_source', $request->source);
        }

        $entries = $query->orderBy('date', 'desc')->get();
        $stores = $this->getAvailableStores();
        $expenseCategories = \App\Models\ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('financial.expenses', compact('entries', 'stores', 'period', 'expenseCategories'));
    }

    public function dailyCloses(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = FinancialEntry::with(['store', 'creator'])
            ->where('type', 'daily_close');

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($request->has('store') && $request->store !== 'all') {
            $query->where('store_id', $request->store);
        }

        $period = $request->get('period', 'last_30');
        $this->applyPeriodFilter($query, $period, $request);

        $entries = $query->orderBy('date', 'desc')->get();
        $stores = $this->getAvailableStores();
        $dailyCloseSettings = $this->getDailyCloseSettings();

        return view('financial.daily-closes', compact('entries', 'stores', 'period', 'dailyCloseSettings'));
    }

    /**
     * Exportar cierres diarios a CSV con los mismos filtros (tienda y fechas) que la vista.
     */
    public function exportDailyCloses(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = FinancialEntry::with(['store'])
            ->where('type', 'daily_close');

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($request->has('store') && $request->store !== 'all') {
            $query->where('store_id', $request->store);
        }

        $period = $request->get('period', 'last_30');
        $this->applyPeriodFilter($query, $period, $request);

        $entries = $query->orderBy('date', 'desc')->get();
        $vouchersEnabled = ($this->getDailyCloseSettings())['vouchers_enabled'] ?? true;

        $filename = 'cierres-diarios-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($entries, $vouchersEnabled) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            $headerRow = ['Fecha', 'Tienda', 'Total (€)', 'TPV (€)', 'Efectivo (€)', 'Discrepancia (€)'];
            if ($vouchersEnabled) {
                array_splice($headerRow, 5, 0, ['Vales (€)']);
            }
            fputcsv($out, $headerRow, ';');

            foreach ($entries as $entry) {
                $discEfectivo = $entry->calculateCashDiscrepancy();
                $discTarjeta = $entry->calculateTpvDiscrepancy();
                $discrepancia = ($discEfectivo ?? 0) + ($discTarjeta ?? 0);

                $total = (float) ($entry->total_amount ?? $entry->amount ?? 0);
                $tpv = $entry->tpv !== null ? (float) $entry->tpv : null;
                $efectivo = $entry->cash_count ? $entry->calculateCashTotal() : null;
                $vales = $vouchersEnabled ? (float) ($entry->vouchers_result ?? 0) : null;

                $row = [
                    $entry->date->format('d/m/Y'),
                    $entry->store->name ?? '',
                    number_format($total, 2, ',', '.'),
                    $tpv !== null ? number_format($tpv, 2, ',', '.') : '',
                    $efectivo !== null ? number_format($efectivo, 2, ',', '.') : '',
                ];
                if ($vouchersEnabled) {
                    $row[] = $vales !== null ? number_format($vales, 2, ',', '.') : '';
                }
                $row[] = $discEfectivo !== null || $discTarjeta !== null ? number_format($discrepancia, 2, ',', '.') : '';

                fputcsv($out, $row, ';');
            }

            fclose($out);
        }, $filename, $headers);
    }

    public function export(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $validated = $request->validate([
            'scope' => 'required|in:income,expenses',
            'format' => 'required|in:csv,xlsx,pdf',
        ]);

        $scope = $validated['scope'];
        $format = $validated['format'];

        $query = FinancialEntry::with(['store', 'invoice'])
            ->where('type', $scope === 'income' ? 'income' : 'expense');

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($request->has('store') && $request->store !== 'all' && $request->store !== '') {
            $query->where('store_id', $request->store);
        }

        $period = $request->get('period', 'last_30');
        $month = $request->get('month');
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                $query->whereRaw("COALESCE(reporting_month, DATE_FORMAT(date, '%Y-%m')) = ?", [$month]);
            } else {
                $query->whereRaw("COALESCE(reporting_month, strftime('%Y-%m', date)) = ?", [$month]);
            }
        } else {
            $this->applyPeriodFilter($query, $period, $request);
        }

        if ($scope === 'income') {
            if ($request->filled('category')) {
                $query->where('income_category', $request->category);
            }
            if ($request->filled('payment_method')) {
                if ($request->payment_method === 'cash') {
                    $query->where('expense_payment_method', 'cash');
                } elseif ($request->payment_method === 'bank') {
                    $query->whereIn('expense_payment_method', ['bank', 'card', 'datafono', 'tarjeta']);
                }
            }
            if ($request->filled('source')) {
                $query->where('income_category', $request->source);
            }
        } else {
            if ($request->filled('category')) {
                $query->where('expense_category', $request->category);
            }
            if ($request->filled('payment_method')) {
                if ($request->payment_method === 'cash') {
                    $query->where('expense_payment_method', 'cash');
                } elseif ($request->payment_method === 'bank') {
                    $query->whereIn('expense_payment_method', ['bank', 'card', 'datafono', 'tarjeta']);
                }
            }
            if ($request->has('only_pending') && $request->only_pending == '1') {
                $query->whereRaw('(COALESCE(total_amount, 0) - COALESCE(paid_amount, 0)) > 0');
            }
            if ($request->filled('source')) {
                $query->where('expense_source', $request->source);
            }
        }

        $entries = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();

        $baseName = ($scope === 'income' ? 'ingresos' : 'gastos').'-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            $filename = $baseName.'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            return response()->streamDownload(function () use ($scope, $entries) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

                if ($scope === 'income') {
                    fputcsv($out, ['Mes correspondiente', 'Fecha', 'Tienda', 'Concepto', 'Categoría', 'Modo de pago', 'Importe (€)'], ';');
                    foreach ($entries as $e) {
                        $pm = $e->expense_payment_method;
                        $pmLabel = $pm === 'cash' ? 'Efectivo' : (in_array($pm, ['bank', 'card', 'datafono', 'tarjeta'], true) ? 'Banco' : '');
                        fputcsv($out, [
                            $e->getReportingMonth(),
                            $e->date?->format('Y-m-d'),
                            $e->store->name ?? '',
                            $e->income_concept ?? $e->concept ?? '',
                            $e->income_category ?? '',
                            $pmLabel,
                            number_format((float) ($e->amount ?? 0), 2, ',', '.'),
                        ], ';');
                    }
                } else {
                    fputcsv($out, ['Mes correspondiente', 'Fecha', 'Tienda', 'Concepto', 'Categoría', 'Modo de pago', 'Procedencia', 'Total (€)', 'Pagado (€)', 'Pendiente (€)', 'Estado', 'Factura'], ';');
                    foreach ($entries as $e) {
                        $pm = $e->expense_payment_method;
                        $pmLabel = $pm === 'cash' ? 'Efectivo' : (in_array($pm, ['bank', 'card', 'datafono', 'tarjeta'], true) ? 'Banco' : '');
                        $total = (float) ($e->total_amount ?? $e->expense_amount ?? $e->amount ?? 0);
                        $paid = (float) ($e->paid_amount ?? 0);
                        $pending = max(0, $total - $paid);
                        fputcsv($out, [
                            $e->getReportingMonth(),
                            $e->date?->format('Y-m-d'),
                            $e->store->name ?? '',
                            $e->expense_concept ?? $e->concept ?? '',
                            $e->expense_category ?? '',
                            $pmLabel,
                            $e->expense_source ?? '',
                            number_format($total, 2, ',', '.'),
                            number_format($paid, 2, ',', '.'),
                            number_format($pending, 2, ',', '.'),
                            $e->status ?? '',
                            $e->invoice?->invoice_number ?? '',
                        ], ';');
                    }
                }

                fclose($out);
            }, $filename, $headers);
        }

        if ($format === 'xlsx') {
            $filename = $baseName.'.xlsx';

            return response()->streamDownload(function () use ($scope, $entries) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
                $sheet = $spreadsheet->getActiveSheet();

                $row = 1;
                if ($scope === 'income') {
                    $headers = ['Mes correspondiente', 'Fecha', 'Tienda', 'Concepto', 'Categoría', 'Modo de pago', 'Importe (€)'];
                } else {
                    $headers = ['Mes correspondiente', 'Fecha', 'Tienda', 'Concepto', 'Categoría', 'Modo de pago', 'Procedencia', 'Total (€)', 'Pagado (€)', 'Pendiente (€)', 'Estado', 'Factura'];
                }
                $sheet->fromArray($headers, null, 'A'.$row);
                $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);

                $row++;
                foreach ($entries as $e) {
                    $pm = $e->expense_payment_method;
                    $pmLabel = $pm === 'cash' ? 'Efectivo' : (in_array($pm, ['bank', 'card', 'datafono', 'tarjeta'], true) ? 'Banco' : '');

                    if ($scope === 'income') {
                        $data = [
                            $e->getReportingMonth(),
                            $e->date?->format('Y-m-d'),
                            $e->store->name ?? '',
                            $e->income_concept ?? $e->concept ?? '',
                            $e->income_category ?? '',
                            $pmLabel,
                            (float) ($e->amount ?? 0),
                        ];
                    } else {
                        $total = (float) ($e->total_amount ?? $e->expense_amount ?? $e->amount ?? 0);
                        $paid = (float) ($e->paid_amount ?? 0);
                        $pending = max(0, $total - $paid);
                        $data = [
                            $e->getReportingMonth(),
                            $e->date?->format('Y-m-d'),
                            $e->store->name ?? '',
                            $e->expense_concept ?? $e->concept ?? '',
                            $e->expense_category ?? '',
                            $pmLabel,
                            $e->expense_source ?? '',
                            $total,
                            $paid,
                            $pending,
                            $e->status ?? '',
                            $e->invoice?->invoice_number ?? '',
                        ];
                    }

                    $sheet->fromArray($data, null, 'A'.$row);
                    $row++;
                }

                foreach (range('A', $sheet->getHighestColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        // PDF
        $filename = $baseName.'.pdf';
        $pdf = new \FPDF('L', 'mm', 'A4');
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $toPdfText = static function (?string $text): string {
            $t = (string) ($text ?? '');
            // FPDF no soporta UTF-8: convertir a Windows-1252 para acentos (Categoría, etc.)
            $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $t);

            return $converted !== false ? $converted : $t;
        };

        $pdf->SetFont('Arial', 'B', 12);
        $title = $scope === 'income' ? 'Ingresos' : 'Gastos';
        $pdf->Cell(0, 8, $toPdfText($title.' - Export '.now()->format('Y-m-d H:i')), 0, 1, 'L');
        $pdf->Ln(1);
        $pdf->SetFont('Arial', '', 7);

        if ($scope === 'income') {
            $cols = [
                ['Mes', 18],
                ['Fecha', 18],
                ['Tienda', 35],
                ['Concepto', 85],
                ['Categoría', 25],
                ['Pago', 18],
                ['Importe', 22],
            ];
            foreach ($cols as [$label, $w]) {
                $pdf->Cell($w, 6, $toPdfText($label), 1, 0, 'C');
            }
            $pdf->Ln();
            foreach ($entries as $e) {
                $pm = $e->expense_payment_method;
                $pmLabel = $pm === 'cash' ? 'Efectivo' : (in_array($pm, ['bank', 'card', 'datafono', 'tarjeta'], true) ? 'Banco' : '');
                $pdf->Cell($cols[0][1], 6, $toPdfText($e->getReportingMonth()), 1);
                $pdf->Cell($cols[1][1], 6, $toPdfText($e->date?->format('d/m/Y') ?? ''), 1);
                $pdf->Cell($cols[2][1], 6, $toPdfText(mb_substr((string) ($e->store->name ?? ''), 0, 22)), 1);
                $pdf->Cell($cols[3][1], 6, $toPdfText(mb_substr((string) ($e->income_concept ?? $e->concept ?? ''), 0, 60)), 1);
                $pdf->Cell($cols[4][1], 6, $toPdfText(mb_substr((string) ($e->income_category ?? ''), 0, 14)), 1);
                $pdf->Cell($cols[5][1], 6, $toPdfText($pmLabel), 1);
                $pdf->Cell($cols[6][1], 6, $toPdfText(number_format((float) ($e->amount ?? 0), 2, ',', '.').' EUR'), 1, 0, 'R');
                $pdf->Ln();
            }
        } else {
            $cols = [
                ['Mes', 18],
                ['Fecha', 18],
                ['Tienda', 28],
                ['Concepto', 55],
                ['Categoría', 24],
                ['Pago', 16],
                ['Origen', 18],
                ['Total', 18],
                ['Pagado', 18],
                ['Pendiente', 18],
                ['Estado', 16],
                ['Factura', 20],
            ];
            foreach ($cols as [$label, $w]) {
                $pdf->Cell($w, 6, $toPdfText($label), 1, 0, 'C');
            }
            $pdf->Ln();
            foreach ($entries as $e) {
                $pm = $e->expense_payment_method;
                $pmLabel = $pm === 'cash' ? 'Efectivo' : (in_array($pm, ['bank', 'card', 'datafono', 'tarjeta'], true) ? 'Banco' : '');
                $total = (float) ($e->total_amount ?? $e->expense_amount ?? $e->amount ?? 0);
                $paid = (float) ($e->paid_amount ?? 0);
                $pending = max(0, $total - $paid);

                $pdf->Cell($cols[0][1], 6, $toPdfText($e->getReportingMonth()), 1);
                $pdf->Cell($cols[1][1], 6, $toPdfText($e->date?->format('d/m/Y') ?? ''), 1);
                $pdf->Cell($cols[2][1], 6, $toPdfText(mb_substr((string) ($e->store->name ?? ''), 0, 16)), 1);
                $pdf->Cell($cols[3][1], 6, $toPdfText(mb_substr((string) ($e->expense_concept ?? $e->concept ?? ''), 0, 34)), 1);
                $pdf->Cell($cols[4][1], 6, $toPdfText(mb_substr((string) ($e->expense_category ?? ''), 0, 14)), 1);
                $pdf->Cell($cols[5][1], 6, $toPdfText($pmLabel), 1);
                $pdf->Cell($cols[6][1], 6, $toPdfText(mb_substr((string) ($e->expense_source ?? ''), 0, 12)), 1);
                $pdf->Cell($cols[7][1], 6, $toPdfText(number_format($total, 2, ',', '.').' EUR'), 1, 0, 'R');
                $pdf->Cell($cols[8][1], 6, $toPdfText(number_format($paid, 2, ',', '.').' EUR'), 1, 0, 'R');
                $pdf->Cell($cols[9][1], 6, $toPdfText(number_format($pending, 2, ',', '.').' EUR'), 1, 0, 'R');
                $pdf->Cell($cols[10][1], 6, $toPdfText(mb_substr((string) ($e->status ?? ''), 0, 10)), 1);
                $pdf->Cell($cols[11][1], 6, $toPdfText(mb_substr((string) ($e->invoice?->invoice_number ?? ''), 0, 14)), 1);
                $pdf->Ln();
            }
        }

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function generateDailyCloseEntries()
    {
        // Implementar generación de registros
        return redirect()->route('financial.index')->with('success', 'Registros generados');
    }

    public function cashControl(Request $request)
    {
        try {
            $this->syncStoresFromBusinesses();

            // Obtener años disponibles para el filtro (compatible MySQL y SQLite)
            try {
                $availableYears = $this->getDistinctYearsFromEntries(FinancialEntry::where('type', 'daily_close'));
            } catch (\Exception $e) {
                $availableYears = [];
            }

            // Asegurar que siempre sea un array
            if (! is_array($availableYears)) {
                $availableYears = [];
            }

            $query = FinancialEntry::with(['store'])
                ->where('type', 'daily_close');

            $enforcedStoreId = auth()->user()->getEnforcedStoreId();
            if ($enforcedStoreId !== null) {
                $query->where('store_id', $enforcedStoreId);
            }

            // Aplicar filtro por año si se especifica (por defecto el año más reciente con datos o el actual)
            $currentYear = date('Y');
            $year = $request->get('year', $currentYear);

            if ($year) {
                $query->whereYear('date', $year);
            }

            $period = $request->get('period', 'all');
            if ($period !== 'all') {
                $this->applyPeriodFilter($query, $period, $request);
            }

            $allEntries = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();

            // Calcular efectivo retirado y agrupar por tienda y mes
            $storesData = [];

            foreach ($allEntries as $entry) {
                $cashCounted = $entry->calculateCashTotal();
                $cashInitial = (float) ($entry->cash_initial ?? 0);
                $cashWithdrawn = round($cashCounted - $cashInitial, 2);

                $storeId = $entry->store_id;
                $storeName = $entry->store->name ?? 'Sin tienda';
                $monthKey = $entry->date->format('Y-m');
                $monthLabel = ucfirst(\Carbon\Carbon::parse($entry->date)->locale('es')->isoFormat('MMMM YYYY'));

                // Inicializar tienda si no existe
                if (! isset($storesData[$storeId])) {
                    $storesData[$storeId] = [
                        'id' => $storeId,
                        'name' => $storeName,
                        'total' => 0,
                        'cash_real' => 0,
                        'cash_collected' => 0,
                        'month_expenses' => 0,
                        'total_traspasos_efectivo' => 0,
                        'balance' => 0,
                        'days_withdrawn' => 0,
                        'days_real' => 0,
                        'months' => [],
                    ];
                }

                // Inicializar mes si no existe
                if (! isset($storesData[$storeId]['months'][$monthKey])) {
                    $storesData[$storeId]['months'][$monthKey] = [
                        'key' => $monthKey,
                        'label' => $monthLabel,
                        'total' => 0,
                        'traspasos_efectivo' => 0,
                        'entries' => [],
                    ];
                }

                // Agregar entrada al mes
                $storesData[$storeId]['months'][$monthKey]['entries'][] = [
                    'id' => $entry->id,
                    'date' => $entry->date->format('d/m/Y'),
                    'amount' => $cashWithdrawn,
                ];

                // Actualizar totales
                $storesData[$storeId]['months'][$monthKey]['total'] += $cashWithdrawn;
                $storesData[$storeId]['total'] += $cashWithdrawn;
            }

            // Calcular efectivo recogido (retiros de carteras) por tienda
            foreach ($storesData as $storeId => &$store) {
                // Calcular efectivo recogido total de la tienda (aplicando filtros de año y período)
                $cashWithdrawalsQuery = CashWithdrawal::where('store_id', $storeId);

                if ($year) {
                    $cashWithdrawalsQuery->whereYear('date', $year);
                }

                if ($period !== 'all') {
                    $this->applyPeriodFilter($cashWithdrawalsQuery, $period, $request);
                }

                $store['cash_collected'] = (float) $cashWithdrawalsQuery->sum('amount');
            }

            // Ordenar meses dentro de cada tienda (más reciente primero)
            foreach ($storesData as &$store) {
                krsort($store['months']);
                $store['months'] = array_reverse($store['months'], true);
            }

            // Calcular totales por tienda
            foreach ($storesData as $storeId => &$store) {
                $daysWithdrawn = [];
                $daysReal = [];

                // Calcular efectivo real y contar días
                foreach ($allEntries as $entry) {
                    if ($entry->store_id == $storeId) {
                        $cashCounted = $entry->calculateCashTotal();
                        $cashInitial = (float) ($entry->cash_initial ?? 0);
                        $cashWithdrawn = round($cashCounted - $cashInitial, 2);

                        if ($cashWithdrawn != 0) {
                            $dayKey = $entry->date->format('Y-m-d');
                            if (! in_array($dayKey, $daysWithdrawn)) {
                                $daysWithdrawn[] = $dayKey;
                            }
                        }

                        if ($entry->cash_real !== null) {
                            $store['cash_real'] += (float) $entry->cash_real;
                            $dayKey = $entry->date->format('Y-m-d');
                            if (! in_array($dayKey, $daysReal)) {
                                $daysReal[] = $dayKey;
                            }
                        }
                    }
                }

                $store['days_withdrawn'] = count($daysWithdrawn);
                $store['days_real'] = count($daysReal);

                // Obtener gastos del mes completo para esta tienda
                $expensesQuery = FinancialEntry::where('type', 'expense')
                    ->where('store_id', $storeId)
                    ->where('notes', 'like', '%"source":"cash_control"%');

                // Aplicar filtro por año también a los gastos
                if ($year) {
                    $expensesQuery->whereYear('date', $year);
                }

                if ($period !== 'all') {
                    $this->applyPeriodFilter($expensesQuery, $period, $request);
                }

                $storeExpenses = $expensesQuery->get();

                foreach ($storeExpenses as $expense) {
                    $procedenceDate = $expense->notes ? json_decode($expense->notes, true) : null;
                    if (is_array($procedenceDate) && isset($procedenceDate['procedence_date'])) {
                        $procDate = $procedenceDate['procedence_date'];
                        // Si la procedencia es el mes completo (formato: 2026-01)
                        if ($procDate !== null && strlen($procDate) === 7) {
                            $amount = (float) ($expense->expense_amount ?? $expense->amount ?? 0);
                            $store['month_expenses'] += $amount;
                        }
                    }
                }

                // Traspasos en efectivo: Transfer reconciliados donde la tienda es origen o destino con fund = cash
                $cashTransfersQuery = Transfer::where('status', 'reconciled')
                    ->whereNotNull('applied_at')
                    ->where(function ($q) use ($storeId) {
                        $q->where(function ($q2) use ($storeId) {
                            $q2->where('origin_type', 'store')->where('origin_id', $storeId)->where('origin_fund', 'cash');
                        })->orWhere(function ($q2) use ($storeId) {
                            $q2->where('destination_type', 'store')->where('destination_id', $storeId)->where('destination_fund', 'cash');
                        });
                    });
                if ($year) {
                    $cashTransfersQuery->whereYear('date', $year);
                }
                if ($period !== 'all') {
                    $this->applyPeriodFilter($cashTransfersQuery, $period, $request);
                }
                $cashTransfers = $cashTransfersQuery->get();
                $store['total_traspasos_efectivo'] = 0;
                foreach ($cashTransfers as $tr) {
                    $amount = (float) $tr->amount;
                    if ($tr->origin_type === 'store' && (int) $tr->origin_id === (int) $storeId && $tr->origin_fund === 'cash') {
                        $store['total_traspasos_efectivo'] -= $amount;
                        $monthKey = $tr->date->format('Y-m');
                        if (isset($store['months'][$monthKey])) {
                            $store['months'][$monthKey]['traspasos_efectivo'] = ($store['months'][$monthKey]['traspasos_efectivo'] ?? 0) - $amount;
                        }
                    }
                    if ($tr->destination_type === 'store' && (int) $tr->destination_id === (int) $storeId && $tr->destination_fund === 'cash') {
                        $store['total_traspasos_efectivo'] += $amount;
                        $monthKey = $tr->date->format('Y-m');
                        if (isset($store['months'][$monthKey])) {
                            $store['months'][$monthKey]['traspasos_efectivo'] = ($store['months'][$monthKey]['traspasos_efectivo'] ?? 0) + $amount;
                        }
                    }
                }

                // Saldo: efectivo real - gastos del mes - efectivo recogido + total traspasos efectivo
                $store['balance'] = round($store['cash_real'] - $store['month_expenses'] - $store['cash_collected'] + $store['total_traspasos_efectivo'], 2);
            }

            $stores = $this->getAvailableStores();

            return view('financial.cash-control', compact('storesData', 'stores', 'period', 'availableYears'));
        } catch (\Exception $e) {
            // En caso de error, devolver vista con datos vacíos
            $stores = $this->getAvailableStores();
            $storesData = [];
            $period = $request->get('period', 'last_30');
            $availableYears = [];

            return view('financial.cash-control', compact('storesData', 'stores', 'period', 'availableYears'));
        }
    }

    public function bankControl(Request $request)
    {
        try {
            $this->syncStoresFromBusinesses();

            // Obtener años disponibles para el filtro (compatible MySQL y SQLite)
            $availableYears = $this->getDistinctYearsFromEntries(FinancialEntry::where('type', 'daily_close'));

            if (! is_array($availableYears)) {
                $availableYears = [];
            }

            // Obtener todas las tiendas disponibles
            $stores = $this->getAvailableStores();

            // Aplicar filtro por tienda solo si el usuario puede elegir (varias tiendas o admin)
            $selectedStoreId = $request->get('store_id');
            if (auth()->user()->getEnforcedStoreId() === null && $selectedStoreId) {
                $stores = $stores->filter(function ($store) use ($selectedStoreId) {
                    return $store->id == $selectedStoreId;
                });
            }

            // Aplicar filtro por año si se especifica (por defecto el año más reciente con datos o el actual)
            $currentYear = date('Y');
            $year = $request->get('year', $currentYear);

            // Obtener periodo
            $period = $request->get('period', 'all');

            // Estructura de datos: tienda -> meses -> totales
            $storesData = [];

            foreach ($stores as $store) {
                $storeId = $store->id;

                // Inicializar estructura de tienda
                $storesData[$storeId] = [
                    'id' => $storeId,
                    'name' => $store->name,
                    'total_income' => 0,
                    'total_expenses' => 0,
                    'total_balance' => 0,
                    'months' => [],
                ];

                // Obtener cierres diarios para calcular ingresos bancarios (tpv)
                $dailyClosesQuery = FinancialEntry::where('type', 'daily_close')
                    ->where('store_id', $storeId);

                if ($year) {
                    $dailyClosesQuery->whereYear('date', $year);
                }

                if ($period !== 'all') {
                    $this->applyPeriodFilter($dailyClosesQuery, $period, $request);
                }

                $dailyCloses = $dailyClosesQuery->get();

                // Obtener transferencias de carteras
                $transfersQuery = CashWalletTransfer::where('store_id', $storeId);

                if ($year) {
                    $transfersQuery->whereYear('date', $year);
                }

                if ($period !== 'all') {
                    // Aplicar filtro de periodo manualmente para CashWalletTransfer
                    $this->applyPeriodFilterToTransfer($transfersQuery, $period, $request);
                }

                $transfers = $transfersQuery->get();

                // Obtener movimientos bancarios conciliados (solo de la empresa actual)
                $bankMovementsQuery = BankMovement::forCurrentCompany()
                    ->where('is_conciliated', true)
                    ->whereHas('bankAccount', function ($query) use ($storeId) {
                        $query->where('store_id', $storeId);
                    });

                if ($year) {
                    $bankMovementsQuery->whereYear('date', $year);
                }

                if ($period !== 'all') {
                    // Aplicar filtro de periodo para BankMovement
                    if ($period === 'last_30') {
                        $bankMovementsQuery->where('date', '>=', now()->subDays(30)->format('Y-m-d'));
                    } elseif ($period === 'this_month') {
                        $bankMovementsQuery->whereYear('date', now()->year)->whereMonth('date', now()->month);
                    } elseif ($period === 'this_year') {
                        $bankMovementsQuery->whereYear('date', now()->year);
                    }
                }

                $bankMovements = $bankMovementsQuery->get();

                // Agrupar por mes y calcular totales
                $monthlyData = [];

                // Procesar cierres diarios (tpv = ingresos bancarios)
                foreach ($dailyCloses as $close) {
                    $monthKey = $close->date->format('Y-m');
                    $monthLabel = ucfirst(\Carbon\Carbon::parse($close->date)->locale('es')->isoFormat('MMMM YYYY'));

                    if (! isset($monthlyData[$monthKey])) {
                        $monthlyData[$monthKey] = [
                            'key' => $monthKey,
                            'label' => $monthLabel,
                            'income' => 0,
                            'expenses' => 0,
                            'transfers' => 0,
                            'balance' => 0,
                        ];
                    }

                    // tpv es el ingreso bancario del cierre diario
                    $tpv = (float) ($close->tpv ?? 0);
                    $monthlyData[$monthKey]['income'] += $tpv;
                }

                // Procesar transferencias de carteras
                foreach ($transfers as $transfer) {
                    $monthKey = $transfer->date->format('Y-m');
                    $monthLabel = ucfirst(\Carbon\Carbon::parse($transfer->date)->locale('es')->isoFormat('MMMM YYYY'));

                    if (! isset($monthlyData[$monthKey])) {
                        $monthlyData[$monthKey] = [
                            'key' => $monthKey,
                            'label' => $monthLabel,
                            'income' => 0,
                            'expenses' => 0,
                            'transfers' => 0,
                            'balance' => 0,
                        ];
                    }

                    $amount = (float) $transfer->amount;
                    $monthlyData[$monthKey]['income'] += $amount;
                }

                // Procesar movimientos bancarios conciliados
                foreach ($bankMovements as $movement) {
                    $monthKey = $movement->date->format('Y-m');
                    $monthLabel = ucfirst(\Carbon\Carbon::parse($movement->date)->locale('es')->isoFormat('MMMM YYYY'));

                    if (! isset($monthlyData[$monthKey])) {
                        $monthlyData[$monthKey] = [
                            'key' => $monthKey,
                            'label' => $monthLabel,
                            'income' => 0,
                            'expenses' => 0,
                            'transfers' => 0,
                            'balance' => 0,
                        ];
                    }

                    $amount = (float) $movement->amount;

                    if ($movement->type === 'transfer' && $movement->status === 'conciliado') {
                        // Traspasos conciliados: restan de la tienda origen
                        $monthlyData[$monthKey]['expenses'] += $amount;
                    } elseif ($movement->type === 'credit') {
                        // Créditos son ingresos
                        $monthlyData[$monthKey]['income'] += $amount;
                    } elseif ($movement->type === 'debit') {
                        // Débitos son gastos
                        $monthlyData[$monthKey]['expenses'] += $amount;
                    }
                }

                // Procesar traspasos recibidos (donde esta tienda es destino)
                $receivedTransfersQuery = BankMovement::where('type', 'transfer')
                    ->where('status', 'conciliado')
                    ->where('destination_store_id', $storeId);

                if ($year) {
                    $receivedTransfersQuery->whereYear('date', $year);
                }

                if ($period !== 'all') {
                    if ($period === 'last_30') {
                        $receivedTransfersQuery->where('date', '>=', now()->subDays(30)->format('Y-m-d'));
                    } elseif ($period === 'this_month') {
                        $receivedTransfersQuery->whereYear('date', now()->year)->whereMonth('date', now()->month);
                    } elseif ($period === 'this_year') {
                        $receivedTransfersQuery->whereYear('date', now()->year);
                    }
                }

                $receivedTransfers = $receivedTransfersQuery->get();

                // Los traspasos recibidos suman al saldo de la tienda destino
                foreach ($receivedTransfers as $transfer) {
                    $monthKey = $transfer->date->format('Y-m');
                    $monthLabel = ucfirst(\Carbon\Carbon::parse($transfer->date)->locale('es')->isoFormat('MMMM YYYY'));

                    if (! isset($monthlyData[$monthKey])) {
                        $monthlyData[$monthKey] = [
                            'key' => $monthKey,
                            'label' => $monthLabel,
                            'income' => 0,
                            'expenses' => 0,
                            'transfers' => 0,
                            'balance' => 0,
                        ];
                    }

                    $amount = (float) $transfer->amount;
                    $monthlyData[$monthKey]['income'] += $amount;
                }

                // Efecto de traspasos reconciliados (Transfer): restan en origen (banco), suman en destino (banco)
                $reconciledTransfersQuery = Transfer::where('status', 'reconciled')
                    ->whereNotNull('applied_at')
                    ->where(function ($q) use ($storeId) {
                        $q->where(function ($q2) use ($storeId) {
                            $q2->where('origin_type', 'store')->where('origin_id', $storeId)->where('origin_fund', 'bank');
                        })->orWhere(function ($q2) use ($storeId) {
                            $q2->where('destination_type', 'store')->where('destination_id', $storeId)->where('destination_fund', 'bank');
                        });
                    });
                if ($year) {
                    $reconciledTransfersQuery->whereYear('date', $year);
                }
                if ($period !== 'all') {
                    if ($period === 'last_30') {
                        $reconciledTransfersQuery->where('date', '>=', now()->subDays(30)->format('Y-m-d'));
                    } elseif ($period === 'this_month') {
                        $reconciledTransfersQuery->whereYear('date', now()->year)->whereMonth('date', now()->month);
                    } elseif ($period === 'this_year') {
                        $reconciledTransfersQuery->whereYear('date', now()->year);
                    }
                }
                $reconciledTransfers = $reconciledTransfersQuery->get();
                foreach ($reconciledTransfers as $tr) {
                    $monthKey = $tr->date->format('Y-m');
                    $monthLabel = ucfirst(\Carbon\Carbon::parse($tr->date)->locale('es')->isoFormat('MMMM YYYY'));
                    if (! isset($monthlyData[$monthKey])) {
                        $monthlyData[$monthKey] = [
                            'key' => $monthKey,
                            'label' => $monthLabel,
                            'income' => 0,
                            'expenses' => 0,
                            'transfers' => 0,
                            'balance' => 0,
                        ];
                    }
                    $amount = (float) $tr->amount;
                    if ($tr->origin_type === 'store' && (int) $tr->origin_id === (int) $storeId && $tr->origin_fund === 'bank') {
                        $monthlyData[$monthKey]['expenses'] += $amount;
                        $monthlyData[$monthKey]['transfers'] = ($monthlyData[$monthKey]['transfers'] ?? 0) - $amount;
                    }
                    if ($tr->destination_type === 'store' && (int) $tr->destination_id === (int) $storeId && $tr->destination_fund === 'bank') {
                        $monthlyData[$monthKey]['income'] += $amount;
                        $monthlyData[$monthKey]['transfers'] = ($monthlyData[$monthKey]['transfers'] ?? 0) + $amount;
                    }
                }

                // Asegurar que todos los meses tengan 'transfers'
                foreach ($monthlyData as $monthKey => &$month) {
                    if (! array_key_exists('transfers', $month)) {
                        $month['transfers'] = 0;
                    }
                }
                unset($month);

                // Calcular saldo por mes y totales
                foreach ($monthlyData as $monthKey => &$month) {
                    $month['balance'] = $month['income'] - $month['expenses'];
                    $storesData[$storeId]['total_income'] += $month['income'];
                    $storesData[$storeId]['total_expenses'] += $month['expenses'];
                }

                $storesData[$storeId]['total_balance'] = $storesData[$storeId]['total_income'] - $storesData[$storeId]['total_expenses'];
                $storesData[$storeId]['months'] = $monthlyData;

                // Ordenar meses (más reciente primero)
                krsort($storesData[$storeId]['months']);
                $storesData[$storeId]['months'] = array_reverse($storesData[$storeId]['months'], true);
            }

            // Convertir a array indexado numéricamente para la vista
            $storesData = array_values($storesData);

            $period = $request->get('period', 'all');
            $allStores = $this->getAvailableStores();

            return view('financial.bank-control', compact('storesData', 'stores', 'allStores', 'period', 'availableYears', 'selectedStoreId', 'year'));

        } catch (\Exception $e) {
            Log::error('Error en bankControl: '.$e->getMessage());
            $stores = $this->getAvailableStores();
            $allStores = $stores;
            $storesData = [];
            $period = $request->get('period', 'all');
            $availableYears = [];
            $selectedStoreId = $request->get('store_id');
            $year = $request->get('year', date('Y'));

            return view('financial.bank-control', compact('storesData', 'stores', 'allStores', 'period', 'availableYears', 'selectedStoreId', 'year'));
        }
    }

    public function cashControlStore($storeId, Request $request)
    {
        $this->syncStoresFromBusinesses();

        $store = Store::findOrFail($storeId);

        $query = FinancialEntry::with(['store'])
            ->where('type', 'daily_close')
            ->where('store_id', $storeId);

        // Aplicar filtro por año si se especifica
        $year = $request->get('year', date('Y'));
        if ($year) {
            $query->whereYear('date', $year);
        }

        // Solo aplicar filtro si el usuario lo especifica explícitamente o hay fechas personalizadas
        $period = $request->get('period', null);

        // Si hay fechas personalizadas o un período específico, aplicar el filtro
        if (($request->has('date_from') && $request->has('date_to') && $request->date_from && $request->date_to) ||
            ($period && $period !== 'all')) {
            if (! $period) {
                $period = 'last_30';
            }
            $this->applyPeriodFilter($query, $period, $request);
        }

        $allEntries = $query->orderBy('date', 'desc')->get();

        $monthsData = [];

        foreach ($allEntries as $entry) {
            $cashCounted = $entry->calculateCashTotal();
            $cashInitial = (float) ($entry->cash_initial ?? 0);
            $cashWithdrawn = round($cashCounted - $cashInitial, 2);

            // Asegurar formato consistente de fecha (Y-m con mes de 2 dígitos)
            $date = \Carbon\Carbon::parse($entry->date);
            $monthKey = $date->format('Y-m'); // Esto siempre genera formato YYYY-MM

            if (! isset($monthsData[$monthKey])) {
                // Generar el label del mes de forma consistente
                $monthLabel = ucfirst($date->locale('es')->isoFormat('MMMM YYYY'));

                $monthsData[$monthKey] = [
                    'key' => $monthKey,
                    'label' => $monthLabel,
                    'total' => 0,
                    'cash_real' => 0,
                    'cash_collected' => 0,
                    'month_expenses' => 0,
                    'traspasos_efectivo' => 0,
                    'balance' => 0,
                    'days_withdrawn' => 0,
                    'days_real' => 0,
                ];
            }

            $monthsData[$monthKey]['total'] += $cashWithdrawn;
        }

        // Traspasos en efectivo por mes (Transfer reconciliados donde la tienda es origen o destino con fund = cash)
        $cashTransfersStoreQuery = Transfer::where('status', 'reconciled')
            ->whereNotNull('applied_at')
            ->where(function ($q) use ($storeId) {
                $q->where(function ($q2) use ($storeId) {
                    $q2->where('origin_type', 'store')->where('origin_id', $storeId)->where('origin_fund', 'cash');
                })->orWhere(function ($q2) use ($storeId) {
                    $q2->where('destination_type', 'store')->where('destination_id', $storeId)->where('destination_fund', 'cash');
                });
            });
        if ($year) {
            $cashTransfersStoreQuery->whereYear('date', $year);
        }
        if ($period && $period !== 'all') {
            $this->applyPeriodFilter($cashTransfersStoreQuery, $period, $request);
        }
        $cashTransfersStore = $cashTransfersStoreQuery->get();
        foreach ($cashTransfersStore as $tr) {
            $monthKey = $tr->date->format('Y-m');
            if (! isset($monthsData[$monthKey])) {
                $monthLabel = ucfirst(\Carbon\Carbon::parse($tr->date)->locale('es')->isoFormat('MMMM YYYY'));
                $monthsData[$monthKey] = [
                    'key' => $monthKey,
                    'label' => $monthLabel,
                    'total' => 0,
                    'cash_real' => 0,
                    'cash_collected' => 0,
                    'month_expenses' => 0,
                    'traspasos_efectivo' => 0,
                    'balance' => 0,
                    'days_withdrawn' => 0,
                    'days_real' => 0,
                ];
            }
            $amount = (float) $tr->amount;
            if ($tr->origin_type === 'store' && (int) $tr->origin_id === (int) $storeId && $tr->origin_fund === 'cash') {
                $monthsData[$monthKey]['traspasos_efectivo'] = ($monthsData[$monthKey]['traspasos_efectivo'] ?? 0) - $amount;
            }
            if ($tr->destination_type === 'store' && (int) $tr->destination_id === (int) $storeId && $tr->destination_fund === 'cash') {
                $monthsData[$monthKey]['traspasos_efectivo'] = ($monthsData[$monthKey]['traspasos_efectivo'] ?? 0) + $amount;
            }
        }

        foreach ($monthsData as $monthKey => &$month) {
            $year = substr($monthKey, 0, 4);
            $monthNum = substr($monthKey, 5, 2);

            // Usar la misma lógica que cashControlMonth: datos del mes completo para que el resumen coincida
            $monthEntries = FinancialEntry::with(['store'])
                ->where('type', 'daily_close')
                ->where('store_id', $storeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $monthNum)
                ->get();

            $month['total'] = 0;
            $month['cash_real'] = 0;
            $daysWithdrawn = [];
            $daysReal = [];
            foreach ($monthEntries as $entry) {
                $cashCounted = $entry->calculateCashTotal();
                $cashInitial = (float) ($entry->cash_initial ?? 0);
                $cashWithdrawn = round($cashCounted - $cashInitial, 2);
                $month['total'] += $cashWithdrawn;
                if ($cashWithdrawn != 0) {
                    $dayKey = $entry->date->format('Y-m-d');
                    if (! in_array($dayKey, $daysWithdrawn)) {
                        $daysWithdrawn[] = $dayKey;
                    }
                }
                if ($entry->cash_real !== null) {
                    $month['cash_real'] += (float) $entry->cash_real;
                    $dayKey = $entry->date->format('Y-m-d');
                    if (! in_array($dayKey, $daysReal)) {
                        $daysReal[] = $dayKey;
                    }
                }
            }
            $month['days_withdrawn'] = count($daysWithdrawn);
            $month['days_real'] = count($daysReal);

            // Efectivo recogido del mes por mes correspondiente (reporting_month)
            $cashMonthCondition = DB::getDriverName() === 'mysql'
                ? 'COALESCE(reporting_month, DATE_FORMAT(date, "%Y-%m")) = ?'
                : "COALESCE(reporting_month, strftime('%Y-%m', date)) = ?";
            $month['cash_collected'] = (float) CashWithdrawal::where('store_id', $storeId)
                ->whereRaw($cashMonthCondition, [$monthKey])
                ->sum('amount');

            // Gastos del mes: (1) control_efectivo con procedence_date este mes, (2) otros gastos en efectivo (no cartera) por mes correspondiente (reporting_month), no por fecha
            $expensesQuery = FinancialEntry::where('type', 'expense')
                ->where('store_id', $storeId)
                ->where(function ($q) {
                    $q->where('expense_source', '!=', 'cierre_diario')->orWhereNull('expense_source');
                })
                ->where(function ($q) use ($monthKey) {
                    $driver = DB::getDriverName();
                    $monthCondition = $driver === 'mysql'
                        ? "COALESCE(reporting_month, DATE_FORMAT(date, '%Y-%m')) = ?"
                        : "COALESCE(reporting_month, strftime('%Y-%m', date)) = ?";
                    $q->where(function ($q1) use ($monthKey) {
                        $q1->where('expense_source', 'control_efectivo')
                            ->where('notes', 'like', '%"procedence_date":"'.$monthKey.'%');
                    })->orWhere(function ($q2) use ($monthKey, $monthCondition) {
                        $q2->where(function ($q3) {
                            $q3->where('expense_source', '!=', 'control_efectivo')->orWhereNull('expense_source');
                        })
                            ->where('expense_payment_method', 'cash')
                            ->whereRaw($monthCondition, [$monthKey])
                            ->whereNotIn('id', CashWalletExpense::select('financial_entry_id')->whereNotNull('financial_entry_id'));
                    });
                });
            $monthExpensesList = $expensesQuery->get();
            $month['month_expenses'] = 0;
            foreach ($monthExpensesList as $e) {
                $amount = (float) ($e->expense_amount ?? $e->amount ?? 0);
                $isControlEfectivo = ($e->expense_source ?? '') === 'control_efectivo';
                $notes = $e->notes ? json_decode($e->notes, true) : null;
                $procDate = is_array($notes) && isset($notes['procedence_date']) ? $notes['procedence_date'] : null;
                // Excluir control_efectivo con procedence_date = día concreto (esos van por día, no al cuadro "Gastos del mes")
                if ($isControlEfectivo && $procDate && strlen($procDate) === 10 && substr($procDate, 0, 7) === $monthKey) {
                    continue;
                }
                $month['month_expenses'] += $amount;
            }
            $month['month_expenses'] = round($month['month_expenses'], 2);

            // Traspasos del mes (ya rellenados antes por cashTransfersStore; recalcular solo por mes para coincidir con vista mes)
            $traspasosEfectivoList = Transfer::where('status', 'reconciled')
                ->whereNotNull('applied_at')
                ->whereYear('date', $year)
                ->whereMonth('date', $monthNum)
                ->where(function ($q) use ($storeId) {
                    $q->where(function ($q2) use ($storeId) {
                        $q2->where('origin_type', 'store')->where('origin_id', $storeId)->where('origin_fund', 'cash');
                    })->orWhere(function ($q2) use ($storeId) {
                        $q2->where('destination_type', 'store')->where('destination_id', $storeId)->where('destination_fund', 'cash');
                    });
                })
                ->get();
            $month['traspasos_efectivo'] = 0;
            foreach ($traspasosEfectivoList as $tr) {
                $amount = (float) $tr->amount;
                if ($tr->origin_type === 'store' && (int) $tr->origin_id === (int) $storeId && $tr->origin_fund === 'cash') {
                    $month['traspasos_efectivo'] -= $amount;
                }
                if ($tr->destination_type === 'store' && (int) $tr->destination_id === (int) $storeId && $tr->destination_fund === 'cash') {
                    $month['traspasos_efectivo'] += $amount;
                }
            }

            $month['balance'] = round($month['cash_real'] - $month['month_expenses'] - $month['cash_collected'] + $month['traspasos_efectivo'], 2);
        }
        unset($month); // Limpiar la referencia después del bucle

        // Calcular el saldo total como la suma de los saldos mensuales
        $storeTotal = 0;
        foreach ($monthsData as $month) {
            $storeTotal += $month['balance'];
        }

        // Asegurar que no haya duplicados antes de ordenar
        $uniqueMonthsData = [];
        foreach ($monthsData as $monthKey => $month) {
            // Si ya existe esta clave, no la añadimos de nuevo
            if (! isset($uniqueMonthsData[$monthKey])) {
                $uniqueMonthsData[$monthKey] = $month;
            }
        }

        // Ordenar meses por fecha descendente (más recientes primero)
        uksort($uniqueMonthsData, function ($a, $b) {
            return strcmp($b, $a); // Orden descendente: 2026-02 antes que 2026-01
        });

        $monthsData = $uniqueMonthsData;

        return view('financial.cash-control-store', compact('store', 'monthsData', 'storeTotal', 'period'));
    }

    public function cashControlMonth($storeId, $monthKey, Request $request)
    {
        $this->syncStoresFromBusinesses();

        $store = Store::findOrFail($storeId);

        $year = substr($monthKey, 0, 4);
        $month = substr($monthKey, 5, 2);

        $query = FinancialEntry::with(['store'])
            ->where('type', 'daily_close')
            ->where('store_id', $storeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        $period = $request->get('period', 'last_30');
        if ($request->has('date_from') && $request->has('date_to') && $request->date_from && $request->date_to) {
            try {
                $start = \Carbon\Carbon::parse($request->date_from)->startOfDay();
                $end = \Carbon\Carbon::parse($request->date_to)->endOfDay();
                $query->whereBetween('date', [$start, $end]);
            } catch (\Exception $e) {
            }
        }

        $entriesSortDate = $request->get('sort_date', 'asc');
        $dateOrder = ($entriesSortDate === 'desc') ? 'desc' : 'asc';
        $entries = $query->orderBy('date', $dateOrder)->orderBy('created_at', $dateOrder)->get();

        // Gastos del mes: (1) control_efectivo con procedence_date este mes, (2) otros gastos en efectivo (no cartera) por mes correspondiente (reporting_month), no por fecha del gasto
        $driver = DB::getDriverName();
        $monthCondition = $driver === 'mysql'
            ? "COALESCE(reporting_month, DATE_FORMAT(date, '%Y-%m')) = ?"
            : "COALESCE(reporting_month, strftime('%Y-%m', date)) = ?";
        $expensesQuery = FinancialEntry::where('type', 'expense')
            ->where('store_id', $storeId)
            ->where(function ($q) {
                $q->where('expense_source', '!=', 'cierre_diario')->orWhereNull('expense_source');
            })
            ->where(function ($q) use ($monthKey, $monthCondition) {
                $q->where(function ($q1) use ($monthKey) {
                    $q1->where('expense_source', 'control_efectivo')
                        ->where('notes', 'like', '%"procedence_date":"'.$monthKey.'%');
                })->orWhere(function ($q2) use ($monthKey, $monthCondition) {
                    $q2->where(function ($q3) {
                        $q3->where('expense_source', '!=', 'control_efectivo')->orWhereNull('expense_source');
                    })
                        ->where('expense_payment_method', 'cash')
                        ->whereRaw($monthCondition, [$monthKey])
                        ->whereNotIn('id', CashWalletExpense::select('financial_entry_id')->whereNotNull('financial_entry_id'));
                });
            });

        if ($request->has('date_from') && $request->has('date_to') && $request->date_from && $request->date_to) {
            try {
                $start = \Carbon\Carbon::parse($request->date_from)->startOfDay();
                $end = \Carbon\Carbon::parse($request->date_to)->endOfDay();
                $expensesQuery->where(function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end])
                        ->orWhere('expense_source', 'control_efectivo');
                });
            } catch (\Exception $e) {
            }
        }

        $allExpenses = $expensesQuery->orderBy('date', 'asc')->get();

        // Agrupar gastos por día (expensesByDay) y gastos del mes (monthExpenses). Solo los que están en la tabla "Gastos del mes" suman al total.
        $expensesByDay = [];
        $monthExpenses = [];

        foreach ($allExpenses as $expense) {
            $amount = (float) ($expense->expense_amount ?? $expense->amount ?? 0);
            $isControlEfectivo = ($expense->expense_source ?? '') === 'control_efectivo';
            $procedenceDate = $expense->notes ? json_decode($expense->notes, true) : null;
            $procDate = is_array($procedenceDate) && isset($procedenceDate['procedence_date']) ? $procedenceDate['procedence_date'] : null;

            if ($isControlEfectivo && $procDate) {
                // Gastos desde "Añadir gasto": con procedence_date día concreto → por día; con procedence_date mes → en "Gastos del mes"
                if (strlen($procDate) === 10 && substr($procDate, 0, 7) === $monthKey) {
                    if (! isset($expensesByDay[$procDate])) {
                        $expensesByDay[$procDate] = [];
                    }
                    $expensesByDay[$procDate][] = [
                        'id' => $expense->id,
                        'date' => $expense->date->format('d/m/Y'),
                        'category' => $expense->expense_category ?? 'otros',
                        'concept' => $expense->expense_concept ?? $expense->concept ?? '—',
                        'amount' => $amount,
                    ];
                } else {
                    $monthExpenses[] = [
                        'id' => $expense->id,
                        'date' => $expense->date->format('d/m/Y'),
                        'category' => $expense->expense_category ?? 'otros',
                        'concept' => $expense->expense_concept ?? $expense->concept ?? '—',
                        'amount' => $amount,
                    ];
                }
            } else {
                // Gastos en efectivo de esta tienda (p. ej. pedidos): se muestran en "Gastos del mes"
                $monthExpenses[] = [
                    'id' => $expense->id,
                    'date' => $expense->date->format('d/m/Y'),
                    'category' => $expense->expense_category ?? 'otros',
                    'concept' => $expense->expense_concept ?? $expense->concept ?? '—',
                    'amount' => $amount,
                ];
            }
        }

        // Total gastos del mes = suma solo de los gastos apuntados en el cuadro "Gastos del mes" (no los de procedencia por día)
        $monthExpensesTotal = round(array_sum(array_column($monthExpenses, 'amount')), 2);

        // Calcular efectivo retirado y gastos por día para cada entrada
        $entries->transform(function ($entry) use ($expensesByDay) {
            $cashCounted = $entry->calculateCashTotal();
            $cashInitial = (float) ($entry->cash_initial ?? 0);
            $cashWithdrawn = round($cashCounted - $cashInitial, 2);
            $entry->cash_withdrawn = $cashWithdrawn;

            // Calcular gastos del día
            $dayKey = $entry->date->format('Y-m-d');
            $dayExpenses = isset($expensesByDay[$dayKey]) ? $expensesByDay[$dayKey] : [];
            $dayExpensesTotal = array_sum(array_column($dayExpenses, 'amount'));
            $entry->day_expenses_total = round($dayExpensesTotal, 2);

            // Calcular efectivo esperado
            $entry->expected_cash = round($cashWithdrawn - $dayExpensesTotal, 2);

            // Obtener efectivo real (si existe)
            $entry->cash_real = $entry->cash_real ?? null;

            return $entry;
        });

        $monthLabel = ucfirst(\Carbon\Carbon::create($year, $month, 1)->locale('es')->isoFormat('MMMM YYYY'));
        $monthTotal = $entries->sum('cash_withdrawn');

        // Calcular efectivo recogido del mes (retiros de carteras): por mes correspondiente (reporting_month)
        $cashMonthCondition = DB::getDriverName() === 'mysql'
            ? 'COALESCE(reporting_month, DATE_FORMAT(date, "%Y-%m")) = ?'
            : "COALESCE(reporting_month, strftime('%Y-%m', date)) = ?";
        $cashWithdrawalsQuery = CashWithdrawal::with(['cashWallet', 'store'])
            ->where('store_id', $storeId)
            ->whereRaw($cashMonthCondition, [$monthKey]);

        $cashWithdrawals = $cashWithdrawalsQuery->orderBy('date', 'desc')->get();
        $totalCashCollected = $cashWithdrawals->sum('amount');

        // Traspasos de efectivo del mes: Transfer reconciliados donde la tienda es origen o destino con fund = cash
        $traspasosEfectivoQuery = Transfer::where('status', 'reconciled')
            ->whereNotNull('applied_at')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where(function ($q) use ($storeId) {
                $q->where(function ($q2) use ($storeId) {
                    $q2->where('origin_type', 'store')->where('origin_id', $storeId)->where('origin_fund', 'cash');
                })->orWhere(function ($q2) use ($storeId) {
                    $q2->where('destination_type', 'store')->where('destination_id', $storeId)->where('destination_fund', 'cash');
                });
            });
        if ($request->has('date_from') && $request->has('date_to') && $request->date_from && $request->date_to) {
            try {
                $start = \Carbon\Carbon::parse($request->date_from)->startOfDay();
                $end = \Carbon\Carbon::parse($request->date_to)->endOfDay();
                $traspasosEfectivoQuery->whereBetween('date', [$start, $end]);
            } catch (\Exception $e) {
            }
        }
        $traspasosEfectivoList = $traspasosEfectivoQuery->get();
        $totalTraspasosEfectivo = 0;
        foreach ($traspasosEfectivoList as $tr) {
            $amount = (float) $tr->amount;
            if ($tr->origin_type === 'store' && (int) $tr->origin_id === (int) $storeId && $tr->origin_fund === 'cash') {
                $totalTraspasosEfectivo -= $amount;
            }
            if ($tr->destination_type === 'store' && (int) $tr->destination_id === (int) $storeId && $tr->destination_fund === 'cash') {
                $totalTraspasosEfectivo += $amount;
            }
        }

        // Calcular totales para el resumen
        $totalCashReal = $entries->sum(function ($entry) {
            return $entry->cash_real !== null ? (float) $entry->cash_real : 0;
        });

        // Saldo del mes = efectivo real - gastos del mes - efectivo recogido + traspasos de efectivo
        $monthBalance = round($totalCashReal - $monthExpensesTotal - $totalCashCollected + $totalTraspasosEfectivo, 2);

        // Obtener días del mes para el formulario
        $days = [];
        $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = \Carbon\Carbon::create($year, $month, $i);
            $days[] = [
                'value' => $date->format('Y-m-d'),
                'label' => $date->format('d/m/Y'),
            ];
        }

        $suppliers = Supplier::orderBy('name')->get();
        $expenseCategories = \App\Models\ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        $firstDay = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $lastDay = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        $dayComments = CashControlDayComment::where('store_id', $storeId)
            ->whereBetween('date', [$firstDay, $lastDay])
            ->get()
            ->keyBy(fn ($c) => $c->date->format('Y-m-d'));

        return view('financial.cash-control-month', compact('store', 'entries', 'monthLabel', 'monthTotal', 'monthKey', 'period', 'expensesByDay', 'monthExpenses', 'monthExpensesTotal', 'days', 'year', 'month', 'totalCashReal', 'monthBalance', 'cashWithdrawals', 'totalCashCollected', 'totalTraspasosEfectivo', 'suppliers', 'entriesSortDate', 'expenseCategories', 'dayComments'));
    }

    public function storeCashControlExpense($storeId, $monthKey, Request $request)
    {
        if (! preg_match('/^\d{4}-\d{2}$/', (string) $monthKey)) {
            abort(404);
        }

        $request->validate([
            'date' => 'required|date',
            'procedence_date' => ['required', 'string', 'regex:/^\d{4}-\d{2}(-\d{2})?$/'],
            'supplier_id' => 'nullable|exists:suppliers,id',
            'expense_category' => 'required|string',
            'expense_concept' => 'required|string',
            'expense_amount' => 'required|numeric',
        ]);

        $procedence = $request->procedence_date;
        $reportingMonth = preg_match('/^\d{4}-\d{2}$/', $procedence)
            ? $procedence
            : substr($procedence, 0, 7);

        $notes = json_encode([
            'procedence_date' => $request->procedence_date,
            'source' => 'cash_control',
        ]);

        $amount = (float) $request->expense_amount;
        FinancialEntry::create([
            'date' => $request->date,
            'reporting_month' => $reportingMonth,
            'store_id' => $storeId,
            'type' => 'expense',
            'expense_category' => $request->expense_category,
            'expense_source' => 'control_efectivo',
            'expense_concept' => $request->expense_concept,
            'expense_amount' => $amount,
            'expense_payment_method' => 'cash',
            'amount' => $amount,
            'total_amount' => $amount,
            'status' => 'pagado',
            'paid_amount' => $amount,
            'concept' => $request->expense_concept,
            'notes' => $notes,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('financial.cash-control-month', [
            'store' => $storeId,
            'month' => $monthKey,
        ])->with('success', 'Gasto añadido correctamente');
    }

    public function storeCashControlDayComment(Store $store, Request $request)
    {
        $this->authorizeStoreAccess($store->id);

        $request->validate([
            'date' => 'required|date',
            'comment' => 'nullable|string|max:2000',
        ]);

        $date = $request->date;
        $comment = $request->filled('comment') ? trim($request->comment) : null;

        $record = CashControlDayComment::withoutGlobalScope(\App\Models\Scopes\BelongsToCompanyScope::class)
            ->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'date' => $date,
                ],
                [
                    'company_id' => $store->company_id,
                    'comment' => $comment,
                ]
            );

        return response()->json([
            'success' => true,
            'comment' => $record->comment,
        ]);
    }

    public function updateCashReal($entryId, Request $request)
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('financial_entries', 'cash_real')) {
                \Illuminate\Support\Facades\Schema::table('financial_entries', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->decimal('cash_real', 10, 2)->nullable()->after('cash_expenses');
                });
            }

            $entry = FinancialEntry::findOrFail($entryId);

            $request->validate([
                'cash_real' => 'nullable|numeric',
            ]);

            $entry->cash_real = $request->has('cash_real') && $request->cash_real !== '' && $request->cash_real !== null
                ? (float) $request->cash_real
                : null;
            $entry->save();

            $cashCounted = $entry->calculateCashTotal();
            $cashInitial = (float) ($entry->cash_initial ?? 0);
            $cashWithdrawn = round($cashCounted - $cashInitial, 2);

            $dayKey = $entry->date->format('Y-m-d');
            $expensesQuery = FinancialEntry::where('type', 'expense')
                ->where('store_id', $entry->store_id)
                ->where('notes', 'like', '%"source":"cash_control"%');

            $allExpenses = $expensesQuery->get();
            $dayExpensesTotal = 0;

            foreach ($allExpenses as $expense) {
                $procedenceDate = $expense->notes ? json_decode($expense->notes, true) : null;
                if (is_array($procedenceDate) && isset($procedenceDate['procedence_date'])) {
                    $procDate = $procedenceDate['procedence_date'];
                    if ($procDate === $dayKey) {
                        $dayExpensesTotal += (float) ($expense->expense_amount ?? $expense->amount ?? 0);
                    }
                }
            }

            $expectedCash = round($cashWithdrawn - $dayExpensesTotal, 2);

            return response()->json([
                'success' => true,
                'cash_real' => $entry->cash_real,
                'expected_cash' => $expectedCash,
                'cash_withdrawn' => $cashWithdrawn,
                'day_expenses_total' => $dayExpensesTotal,
            ]);
        } catch (\Exception $e) {
            Log::error('Error actualizando efectivo real: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el efectivo real: '.$e->getMessage(),
            ], 500);
        }
    }

    public function createCashWithdrawal(Request $request)
    {
        $this->syncStoresFromBusinesses();
        $stores = $this->getAvailableStores();
        $cashWallets = CashWallet::all();
        $availableMonths = $this->getAvailableMonthsForCashControl();
        // Desde la vista de un mes: predeterminar mes correspondiente y tienda
        $defaultReportingMonth = $request->get('reporting_month');
        $defaultStoreId = $request->get('store_id');
        $redirectTo = $request->get('redirect_to');

        return view('financial.cash-withdrawals.create', compact('stores', 'cashWallets', 'availableMonths', 'defaultReportingMonth', 'defaultStoreId', 'redirectTo'));
    }

    public function storeCashWithdrawal(Request $request)
    {
        if (! $request->filled('reporting_month') && $request->filled('date')) {
            $request->merge([
                'reporting_month' => \Carbon\Carbon::parse((string) $request->input('date'))->format('Y-m'),
            ]);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reporting_month' => 'required|date_format:Y-m',
            'store_id' => 'required|exists:stores,id',
            'cash_wallet_id' => 'required|exists:cash_wallets,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        CashWithdrawal::create([
            'date' => $validated['date'],
            'reporting_month' => $validated['reporting_month'],
            'store_id' => $validated['store_id'],
            'cash_wallet_id' => $validated['cash_wallet_id'],
            'amount' => $validated['amount'],
            'created_by' => Auth::id(),
        ]);

        $redirect = route('financial.cash-control');
        $redirectTo = $request->get('redirect_to');
        if ($redirectTo === 'dashboard') {
            $redirect = route('dashboard');
        } elseif ($redirectTo && \Illuminate\Support\Str::startsWith($redirectTo, [url('/'), config('app.url')])) {
            $redirect = $redirectTo;
        }

        return redirect($redirect)->with('success', 'Retiro de efectivo registrado correctamente');
    }

    /**
     * Meses con registro en control de efectivo (cierres diarios o recogidas) para las tiendas accesibles.
     */
    protected function getAvailableMonthsForCashControl(): array
    {
        $storeIds = $this->getAvailableStores()->pluck('id')->toArray();
        if (empty($storeIds)) {
            return [];
        }
        $monthExpr = DB::getDriverName() === 'mysql'
            ? "DATE_FORMAT(date, '%Y-%m') as month_val"
            : "strftime('%Y-%m', date) as month_val";
        $fromEntries = FinancialEntry::where('type', 'daily_close')
            ->whereIn('store_id', $storeIds)
            ->selectRaw($monthExpr)
            ->distinct()
            ->pluck('month_val');
        $fromWithdrawals = CashWithdrawal::whereIn('store_id', $storeIds)
            ->whereNotNull('reporting_month')
            ->distinct()
            ->pluck('reporting_month');

        return $fromEntries->merge($fromWithdrawals)->unique()->sortDesc()->values()->toArray();
    }

    /**
     * Ingresar dinero: cartera → tienda (traspaso)
     */
    public function storeCashDeposit(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'cash_wallet_id' => 'required|exists:cash_wallets,id',
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $wallet = CashWallet::findOrFail($validated['cash_wallet_id']);
        $balance = $this->calculateWalletBalance($wallet);
        $redirect = $request->get('redirect_to') === 'dashboard'
            ? route('dashboard')
            : url()->previous();
        if ($balance < $validated['amount']) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'La cartera no tiene saldo suficiente.'], 422);
            }

            return redirect($redirect)->with('error', 'La cartera no tiene saldo suficiente. Saldo disponible: '.number_format($balance, 2, ',', '.').' €');
        }

        try {
            $cashWalletTransfer = CashWalletTransfer::create([
                'cash_wallet_id' => $validated['cash_wallet_id'],
                'store_id' => $validated['store_id'],
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'created_by' => Auth::id(),
            ]);

            // Crear registro Transfer para gestionar el efecto en el banco de la tienda
            $transfer = Transfer::create([
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'origin_type' => 'wallet',
                'origin_id' => $wallet->id,
                'origin_fund' => 'cash',
                'destination_type' => 'store',
                'destination_id' => $validated['store_id'],
                'destination_fund' => 'bank',
                'method' => 'manual',
                'status' => 'pending',
                'notes' => 'Ingreso de efectivo desde cartera '.$wallet->name,
                'created_by' => Auth::id(),
            ]);

            $transfer->update(['status' => 'reconciled']);
            $transfer->refresh();

            $result = $transfer->apply();
            if (! $result['success']) {
                $transfer->update(['status' => 'pending']);
                $cashWalletTransfer->delete();
                $transfer->delete();
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result['message'] ?? 'Error al aplicar la transferencia.'], 422);
                }

                return redirect($redirect)->with('error', $result['message'] ?? 'Error al aplicar la transferencia. Verifica los datos.');
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Ingreso de efectivo registrado correctamente']);
            }

            return redirect($redirect)->with('success', 'Ingreso de efectivo registrado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al registrar ingreso de efectivo desde cartera', [
                'cash_wallet_id' => $validated['cash_wallet_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al registrar la transferencia: '.$e->getMessage()], 500);
            }

            return redirect($redirect)->with('error', 'Error al registrar la transferencia: '.$e->getMessage());
        }
    }

    private function calculateWalletBalance(CashWallet $wallet): float
    {
        $withdrawals = CashWithdrawal::where('cash_wallet_id', $wallet->id)->sum('amount');
        $expenses = CashWalletExpense::where('cash_wallet_id', $wallet->id)->whereHas('financialEntry')->sum('amount');
        $transfers = CashWalletTransfer::where('cash_wallet_id', $wallet->id)->sum('amount');

        return round($withdrawals - $expenses - $transfers, 2);
    }

    /**
     * Mostrar vista de conciliación bancaria
     */
    public function bankConciliation(Request $request)
    {
        $this->syncStoresFromBusinesses();

        // Obtener todas las cuentas bancarias
        $bankAccounts = BankAccount::with('store')->orderBy('bank_name')->get();

        // Obtener tiendas disponibles
        $stores = $this->getAvailableStores();

        // Construir query con filtros (solo movimientos de cuentas de la empresa actual)
        $query = BankMovement::forCurrentCompany()
            ->with(['bankAccount.store', 'financialEntry', 'destinationStore']);

        // Filtro por tienda (a través de bank_account): forzada si el usuario solo tiene una
        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->whereHas('bankAccount', function ($q) use ($enforcedStoreId) {
                $q->where('store_id', $enforcedStoreId);
            });
        } elseif ($request->has('store_id') && $request->store_id) {
            $query->whereHas('bankAccount', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        // Filtro por fecha desde
        if ($request->has('date_from') && $request->date_from) {
            try {
                $dateFrom = \Carbon\Carbon::parse($request->date_from)->startOfDay();
                $query->where('date', '>=', $dateFrom);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }

        // Filtro por fecha hasta
        if ($request->has('date_to') && $request->date_to) {
            try {
                $dateTo = \Carbon\Carbon::parse($request->date_to)->endOfDay();
                $query->where('date', '<=', $dateTo);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }

        // Filtro por estado: por defecto (primera carga) solo pendientes; "Todos" muestra todos
        if ($request->has('status')) {
            if ($request->status === 'conciliado') {
                $query->where('is_conciliated', true);
            } elseif ($request->status === 'pendiente') {
                $query->where('is_conciliated', false);
            }
            // status === '' (Todos): no filtrar por is_conciliated
        } else {
            // Primera carga: solo movimientos pendientes de conciliar
            $query->where('is_conciliated', false);
        }

        // Filtro por tipo
        if ($request->has('type') && $request->type !== '') {
            if ($request->type === 'gasto') {
                $query->where('type', 'debit');
            } elseif ($request->type === 'ingreso') {
                $query->where('type', 'credit');
            } elseif ($request->type === 'traspaso') {
                $query->where('type', 'transfer');
            }
        }

        // Ordenar por fecha desc
        $movements = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener transfers relacionados con los movimientos bancarios
        // Ahora los bank_movements tienen transfer_id, así que buscamos transfers por esos IDs
        $transferIds = $movements->whereNotNull('transfer_id')->pluck('transfer_id')->unique()->toArray();
        $relatedTransfers = Transfer::whereIn('id', $transferIds)
            ->with(['origin', 'destination', 'creator', 'bankMovements'])
            ->get()
            ->keyBy('id');

        // Crear un mapa de bank_movement_id -> transfer para la vista
        $movementToTransferMap = [];
        foreach ($movements as $movement) {
            if ($movement->transfer_id && isset($relatedTransfers[$movement->transfer_id])) {
                $movementToTransferMap[$movement->id] = $relatedTransfers[$movement->transfer_id];
            }
        }

        $expenseCategories = \App\Models\ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        $loans = \App\Models\Loan::orderBy('name')->get();

        // Ingresos (datáfono/banco) con conciliación parcial: suma de movimientos < importe y sin gasto por diferencia creado
        $partialReconciliationIncomes = collect();
        $incomeIdsWithLinkedMovements = BankMovement::whereNotNull('financial_entry_id')
            ->whereIn('financial_entry_id', FinancialEntry::where('type', 'income')->where('expense_payment_method', 'bank')->pluck('id'))
            ->selectRaw('financial_entry_id, SUM(amount) as total_linked')
            ->groupBy('financial_entry_id')
            ->get();
        foreach ($incomeIdsWithLinkedMovements as $row) {
            $income = FinancialEntry::find($row->financial_entry_id);
            if (! $income || ! $this->userCanAccessStore($income->store_id)) {
                continue;
            }
            $incomeAmount = (float) ($income->total_amount ?? $income->amount ?? $income->income_amount ?? 0);
            $linkedSum = (float) $row->total_linked;
            $diff = round($incomeAmount - $linkedSum, 2);
            $hasTpvDiffExpense = FinancialEntry::where('source_income_id', $income->id)->where('type', 'expense')->exists();
            if ($diff > 0 && ! $hasTpvDiffExpense) {
                $partialReconciliationIncomes->push((object) [
                    'entry' => $income,
                    'linked_sum' => $linkedSum,
                    'income_amount' => $incomeAmount,
                    'difference' => $diff,
                ]);
            }
        }

        return view('financial.bank-conciliation', compact('movements', 'bankAccounts', 'stores', 'relatedTransfers', 'movementToTransferMap', 'expenseCategories', 'loans', 'partialReconciliationIncomes'));
    }

    private function userCanAccessStore($storeId): bool
    {
        $enforced = auth()->user()->getEnforcedStoreId();
        if ($enforced !== null) {
            return (int) $storeId === (int) $enforced;
        }

        return auth()->user()->canAccessStore((int) $storeId);
    }

    /**
     * Enlazar movimiento bancario a gasto existente
     */
    public function linkBankMovementToExpense(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'financial_entry_id' => 'required|exists:financial_entries,id',
        ]);

        try {
            $entry = FinancialEntry::findOrFail($validated['financial_entry_id']);
            if ($entry->type !== 'expense') {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'El registro seleccionado no es un gasto.'], 422);
                }

                return back()->with('error', 'El registro seleccionado no es un gasto.');
            }
            // Marcar bankMovement como conciliado y guardar financial_entry_id
            $bankMovement->update([
                'is_conciliated' => true,
                'status' => 'conciliado',
                'financial_entry_id' => $validated['financial_entry_id'],
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario enlazado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error enlazando movimiento bancario: '.$e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al enlazar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Enlazar movimiento bancario (ingreso/crédito) a ingreso existente
     */
    public function linkBankMovementToIncome(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'financial_entry_id' => 'required|exists:financial_entries,id',
        ]);

        try {
            $entry = FinancialEntry::findOrFail($validated['financial_entry_id']);
            if ($entry->type !== 'income') {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'El registro seleccionado no es un ingreso.'], 422);
                }

                return back()->with('error', 'El registro seleccionado no es un ingreso.');
            }
            $bankMovement->update([
                'is_conciliated' => true,
                'status' => 'conciliado',
                'financial_entry_id' => $validated['financial_entry_id'],
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario enlazado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error enlazando movimiento bancario a ingreso: '.$e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al enlazar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Crear gasto por la diferencia TPV (ingreso datáfono/banco con movimientos conciliados que no cubren el total).
     * Solo una vez por ingreso; categoría y concepto editables (por defecto Servicios profesionales, Tasa TPV DD/MM/AAAA).
     */
    public function storeTpvDifferenceExpense(Request $request, FinancialEntry $entry)
    {
        $this->authorizeStoreAccess($entry->store_id);

        if ($entry->type !== 'income' || ($entry->expense_payment_method ?? '') !== 'bank') {
            return back()->with('error', 'Solo se puede crear el gasto por diferencia en ingresos de datáfono/banco.');
        }

        $linkedSum = (float) $entry->bankMovements()->sum('amount');
        $incomeAmount = (float) ($entry->total_amount ?? $entry->amount ?? $entry->income_amount ?? 0);
        $difference = round($incomeAmount - $linkedSum, 2);

        if ($difference <= 0) {
            return back()->with('error', 'No hay diferencia pendiente (la suma de movimientos ya cubre o supera el ingreso).');
        }

        if (FinancialEntry::where('source_income_id', $entry->id)->where('type', 'expense')->exists()) {
            return back()->with('error', 'Ya existe un gasto por la diferencia de este ingreso. No se puede crear otro.');
        }

        $validated = $request->validate([
            'expense_category' => 'required|string|max:255',
            'expense_concept' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:'.($difference + 0.01),
        ]);

        $amount = round((float) $validated['amount'], 2);
        if ($amount > $difference) {
            return back()->with('error', 'El importe no puede superar la diferencia ('.number_format($difference, 2, ',', '.').' €).');
        }

        try {
            FinancialEntry::create([
                'date' => $entry->date,
                'reporting_month' => $entry->getReportingMonth(),
                'store_id' => $entry->store_id,
                'type' => 'expense',
                'expense_payment_method' => 'bank',
                'expense_amount' => $amount,
                'amount' => $amount,
                'total_amount' => $amount,
                'status' => 'pagado',
                'paid_amount' => $amount,
                'expense_category' => $validated['expense_category'],
                'expense_source' => 'diferencia_tpv',
                'expense_concept' => $validated['expense_concept'],
                'concept' => $validated['expense_concept'],
                'source_income_id' => $entry->id,
                'notes' => json_encode(['source' => 'tpv_difference', 'income_id' => $entry->id]),
                'created_by' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creando gasto por diferencia TPV: '.$e->getMessage());

            return back()->with('error', 'Error al crear el gasto: '.$e->getMessage());
        }

        $redirectTo = $request->get('redirect_to');
        if ($redirectTo && is_string($redirectTo)) {
            $host = parse_url($redirectTo, PHP_URL_HOST);
            if ($host === null && str_starts_with($redirectTo, '/') || ($host !== null && $host === $request->getHost())) {
                return redirect($redirectTo)->with('success', 'Gasto por la diferencia TPV creado correctamente.');
            }
        }

        return redirect()->route('financial.show', $entry)->with('success', 'Gasto por la diferencia TPV creado correctamente.');
    }

    /**
     * Crear gasto desde movimiento bancario
     */
    public function createExpenseFromBankMovement(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'reporting_month' => 'required|date_format:Y-m',
            'store_id' => 'required|exists:stores,id',
            'expense_category' => 'nullable|string|max:255',
            'expense_concept' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        try {
            // Crear FinancialEntry (expense)
            $financialEntry = FinancialEntry::create([
                'date' => $validated['date'],
                'reporting_month' => $validated['reporting_month'],
                'store_id' => $validated['store_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'type' => 'expense',
                'expense_payment_method' => 'bank',
                'expense_amount' => $validated['amount'],
                'amount' => $validated['amount'],
                'total_amount' => $validated['amount'],
                'status' => 'pagado',
                'paid_amount' => $validated['amount'],
                'expense_category' => $validated['expense_category'] ?? null,
                'expense_source' => 'conciliacion_bancaria',
                'expense_concept' => $validated['expense_concept'],
                'concept' => $validated['expense_concept'],
                'notes' => json_encode([
                    'source' => 'bank_movement',
                    'bank_movement_id' => $bankMovement->id,
                ]),
                'created_by' => Auth::id(),
            ]);

            // Marcar bankMovement como conciliado y guardar financial_entry_id
            $bankMovement->update([
                'is_conciliated' => true,
                'status' => 'conciliado',
                'financial_entry_id' => $financialEntry->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Gasto creado y movimiento conciliado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error creando gasto desde movimiento bancario: '.$e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al crear el gasto: '.$e->getMessage());
        }
    }

    /**
     * Conciliar movimiento bancario como traspaso
     * Crea UN único transfer y enlaza el bank_movement a él
     */
    public function conciliateAsTransfer(Request $request, BankMovement $bankMovement)
    {
        // Verificar que el movimiento sea de tipo transfer
        if ($bankMovement->type !== 'transfer') {
            return back()->with('error', 'Este movimiento no es de tipo traspaso.');
        }

        // Verificar que no esté ya conciliado
        if ($bankMovement->is_conciliated) {
            return back()->with('error', 'Este movimiento ya está conciliado.');
        }

        // Verificar si ya está enlazado a un transfer
        if ($bankMovement->transfer_id) {
            return back()->with('error', 'Este movimiento ya está enlazado a un traspaso. Si necesitas modificarlo, edita el traspaso directamente.');
        }

        $currentStoreId = $bankMovement->bankAccount->store_id;
        $amount = abs((float) $bankMovement->amount); // Siempre positivo

        // Determinar el signo desde raw_description ([NEG] / [POS])
        $isNegative = str_starts_with($bankMovement->raw_description ?? '', '[NEG]');

        // Validación condicional según el signo
        if ($isNegative) {
            // amount < 0: se pide SOLO tienda destino. Origen = tienda del movimiento (fijo).
            $validated = $request->validate([
                'destination_store_id' => 'required|exists:stores,id',
                'destination_fund' => 'required|in:bank,cash',
            ]);
        } else {
            // amount > 0: se pide SOLO tienda origen. Destino = tienda del movimiento (fijo).
            $validated = $request->validate([
                'origin_store_id' => 'required|exists:stores,id',
            ]);
            $validated['origin_fund'] = 'bank';
        }

        try {
            // Determinar origen y destino según el signo
            // amount < 0: la tienda del movimiento ES el origen. Destino = lo que el usuario elige.
            // amount > 0: la tienda del movimiento ES el destino. Origen = lo que el usuario elige.
            if ($isNegative) {
                $originType = 'store';
                $originId = $currentStoreId;
                $originFund = 'bank';
                $destinationType = 'store';
                $destinationId = $validated['destination_store_id'];
                $destinationFund = $validated['destination_fund'];
            } else {
                $originType = 'store';
                $originId = $validated['origin_store_id'];
                $originFund = 'bank';
                $destinationType = 'store';
                $destinationId = $currentStoreId;
                $destinationFund = 'bank';
            }

            // Validar que origen ≠ destino
            if ($originId == $destinationId && $originFund === $destinationFund) {
                return back()->with('error', 'El origen y el destino no pueden ser iguales.');
            }

            // Verificar si ya existe un transfer para este traspaso (por si se importó el otro banco primero)
            $existingTransfer = $this->findExistingTransferForBankMovement(
                $bankMovement,
                $amount,
                $bankMovement->date,
                $currentStoreId,
                $isNegative
            );

            if ($existingTransfer) {
                // Enlazar el bank_movement al transfer existente: conciliado y no pendiente
                $bankMovement->update([
                    'transfer_id' => $existingTransfer->id,
                    'is_conciliated' => true,
                    'status' => 'conciliado',
                ]);

                return redirect()->route('financial.bank-conciliation')
                    ->with('success', 'Movimiento bancario enlazado a traspaso existente correctamente.');
            }

            // Concepto del traspaso: "Traspaso bancario: {origen} → {destino}"
            $originStore = Store::find($originId);
            $destinationStore = Store::find($destinationId);
            $conceptText = 'Traspaso bancario: '.($originStore ? $originStore->name : '').' → '.($destinationStore ? $destinationStore->name : '');

            // Crear el Transfer (UN único transfer para este traspaso)
            $transfer = Transfer::create([
                'date' => $bankMovement->date,
                'amount' => $amount, // Siempre positivo
                'origin_type' => $originType,
                'origin_id' => $originId,
                'origin_fund' => $originFund,
                'destination_type' => $destinationType,
                'destination_id' => $destinationId,
                'destination_fund' => $destinationFund,
                'method' => 'bank_import',
                'status' => 'pending', // Crear como pending inicialmente
                'notes' => json_encode([
                    'source' => 'bank_conciliation',
                    'concept' => $conceptText,
                ]),
                'created_by' => Auth::id(),
            ]);

            // Enlazar el bank_movement al transfer: conciliado y no pendiente
            $bankMovement->update([
                'transfer_id' => $transfer->id,
                'is_conciliated' => true,
                'status' => 'conciliado',
            ]);

            // Actualizar el status a 'reconciled' ANTES de llamar a apply()
            $transfer->update(['status' => 'reconciled']);
            $transfer->refresh();

            // Aplicar la transferencia (UNA sola vez)
            $result = $transfer->apply();
            if (! $result['success']) {
                // Si falla, revertir el status y desenlazar
                $transfer->update(['status' => 'pending']);
                $bankMovement->update([
                    'transfer_id' => null,
                    'is_conciliated' => false,
                    'status' => 'pendiente',
                ]);
                $transfer->delete();

                return back()->with('error', $result['message'] ?? 'Error al aplicar la transferencia. Verifica los datos.');
            }

            // Asegurar que el movimiento quede persistido como conciliado (por si apply() modificara algo)
            $bankMovement->refresh();
            if (! $bankMovement->is_conciliated || $bankMovement->status !== 'conciliado') {
                $bankMovement->update(['is_conciliated' => true, 'status' => 'conciliado']);
            }

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario conciliado como traspaso correctamente.');

        } catch (\Exception $e) {
            Log::error('Error conciliando movimiento bancario como traspaso: '.$e->getMessage(), [
                'bank_movement_id' => $bankMovement->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al conciliar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Buscar un transfer existente que coincida con un bank_movement
     * Busca por: importe (abs), fecha (±1 día), y tiendas cruzadas
     *
     * IMPORTANTE: Un transfer puede tener varios bank_movements enlazados (uno por cada banco).
     * Este método busca transfers donde la tienda del bank_movement coincida con origen o destino,
     * pero que NO tenga ya un bank_movement de la misma tienda enlazado.
     */
    protected function findExistingTransferForBankMovement(
        BankMovement $bankMovement,
        float $amount,
        $date,
        int $currentStoreId,
        bool $isNegative
    ): ?Transfer {
        // Buscar transfers con el mismo importe y fecha similar (±1 día)
        $dateFrom = \Carbon\Carbon::parse($date)->subDay();
        $dateTo = \Carbon\Carbon::parse($date)->addDay();

        $transfers = Transfer::where('amount', $amount)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('method', 'bank_import')
            ->with('bankMovements') // Cargar bank_movements relacionados
            ->get();

        foreach ($transfers as $transfer) {
            // Verificar que las tiendas sean cruzadas (una es origen y otra destino)
            $matches = false;

            if ($isNegative) {
                // Este bank_movement es negativo: la tienda actual es destino
                // Buscar transfers donde la tienda actual sea destino
                if ($transfer->destination_type === 'store' &&
                    $transfer->destination_id === $currentStoreId &&
                    $transfer->destination_fund === 'bank') {
                    $matches = true;
                }
            } else {
                // Este bank_movement es positivo: la tienda actual es origen
                // Buscar transfers donde la tienda actual sea origen
                if ($transfer->origin_type === 'store' &&
                    $transfer->origin_id === $currentStoreId &&
                    $transfer->origin_fund === 'bank') {
                    $matches = true;
                }
            }

            if ($matches) {
                // Verificar que no haya ya un bank_movement de esta tienda enlazado a este transfer
                $hasBankMovementFromThisStore = $transfer->bankMovements()
                    ->whereHas('bankAccount', function ($query) use ($currentStoreId) {
                        $query->where('store_id', $currentStoreId);
                    })
                    ->exists();

                // Si no hay bank_movement de esta tienda, podemos enlazar este
                if (! $hasBankMovementFromThisStore) {
                    return $transfer;
                }
            }
        }

        return null;
    }

    /**
     * Eliminar movimiento bancario y todos sus registros relacionados
     */
    public function destroyBankMovement(BankMovement $bankMovement)
    {
        try {
            DB::beginTransaction();
            $this->deleteBankMovementAndRelated($bankMovement);
            DB::commit();

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario y todos sus registros relacionados eliminados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando movimiento bancario: '.$e->getMessage(), [
                'bank_movement_id' => $bankMovement->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al eliminar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Eliminar varios movimientos bancarios a la vez
     */
    public function destroyBulkBankMovements(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|min:1',
        ]);

        $movements = BankMovement::forCurrentCompany()
            ->whereIn('id', $validated['ids'])
            ->get();

        if ($movements->isEmpty()) {
            return back()->with('error', 'No se encontraron movimientos válidos para eliminar.');
        }

        $deleted = 0;
        try {
            DB::beginTransaction();
            foreach ($movements as $bankMovement) {
                $this->deleteBankMovementAndRelated($bankMovement);
                $deleted++;
            }
            DB::commit();
            $message = $deleted === 1
                ? 'Movimiento bancario eliminado correctamente.'
                : $deleted.' movimientos bancarios eliminados correctamente.';

            return redirect()->route('financial.bank-conciliation')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando movimientos bancarios en lote: '.$e->getMessage(), [
                'ids' => $validated['ids'],
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al eliminar los movimientos: '.$e->getMessage());
        }
    }

    /**
     * Lógica interna: eliminar un movimiento bancario y sus registros relacionados (transfer, financial_entry).
     * Debe ejecutarse dentro de una transacción.
     */
    private function deleteBankMovementAndRelated(BankMovement $bankMovement): void
    {
        if ($bankMovement->transfer_id) {
            $transfer = Transfer::find($bankMovement->transfer_id);
            if ($transfer) {
                $linkedMovementsCount = BankMovement::forCurrentCompany()->where('transfer_id', $transfer->id)->count();
                if ($linkedMovementsCount === 1) {
                    if ($transfer->status === 'reconciled') {
                        $rollbackResult = $transfer->rollback();
                        if (! $rollbackResult['success']) {
                            throw new \RuntimeException('Error al revertir el traspaso relacionado: '.($rollbackResult['message'] ?? 'Error desconocido'));
                        }
                        $transfer->update(['status' => 'pending']);
                    }
                    $transfer->delete();
                } else {
                    $bankMovement->update(['transfer_id' => null, 'is_conciliated' => false]);
                }
            }
        }

        if ($bankMovement->financial_entry_id) {
            $financialEntry = FinancialEntry::find($bankMovement->financial_entry_id);
            if ($financialEntry) {
                ExpensePayment::where('financial_entry_id', $financialEntry->id)->delete();
                $financialEntry->delete();
            }
        }

        $bankMovement->delete();
    }

    /**
     * Ignorar movimiento bancario
     */
    public function ignoreBankMovement(BankMovement $bankMovement)
    {
        try {
            // Marcar como conciliado = false
            // Marcar como ignored = true (si existe el campo)
            $updateData = [
                'is_conciliated' => false,
            ];

            // Verificar si existe el campo 'ignored' en la tabla
            if (Schema::hasColumn('bank_movements', 'ignored')) {
                $updateData['ignored'] = true;
            }

            $bankMovement->update($updateData);

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario marcado como ignorado.');

        } catch (\Exception $e) {
            Log::error('Error ignorando movimiento bancario: '.$e->getMessage());

            return back()->with('error', 'Error al ignorar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Obtener gastos disponibles para conciliación (AJAX)
     */
    public function getAvailableExpenses(Request $request)
    {
        $storeId = $request->get('store_id');
        $amount = (float) $request->get('amount');

        if (! $storeId || ! $amount) {
            return response()->json(['expenses' => []]);
        }

        // Buscar gastos que coincidan con la tienda e importe (mismo importe)
        $expenses = FinancialEntry::where('store_id', $storeId)
            ->where('type', 'expense')
            ->where(function ($query) use ($amount) {
                $query->whereBetween('amount', [$amount - 0.01, $amount + 0.01])
                    ->orWhereBetween('total_amount', [$amount - 0.01, $amount + 0.01])
                    ->orWhereBetween('expense_amount', [$amount - 0.01, $amount + 0.01]);
            })
            ->whereNull('deleted_at')
            ->orderBy('date', 'desc')
            ->select('id', 'date', 'concept', 'expense_concept', 'amount', 'total_amount', 'expense_amount')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'date' => $entry->date->format('d/m/Y'),
                    'concept' => $entry->expense_concept ?? $entry->concept ?? 'Gasto',
                    'amount' => $entry->expense_amount ?? $entry->total_amount ?? $entry->amount,
                ];
            });

        return response()->json(['expenses' => $expenses]);
    }

    /**
     * Conciliar movimiento bancario (enlazar existente o crear nuevo)
     */
    public function conciliateBankMovement(Request $request, $id)
    {
        try {
            $bankMovement = BankMovement::forCurrentCompany()->findOrFail($id);

            // Si viene financial_entry_id, enlazar existente
            if ($request->has('financial_entry_id') && $request->financial_entry_id) {
                $validated = $request->validate([
                    'financial_entry_id' => 'required|exists:financial_entries,id',
                ]);

                $financialEntry = FinancialEntry::findOrFail($validated['financial_entry_id']);

                $bankMovement->update([
                    'is_conciliated' => true,
                    'financial_entry_id' => $financialEntry->id,
                ]);

                return redirect()->route('financial.bank-conciliation')
                    ->with('success', 'Movimiento bancario conciliado correctamente.');
            }

            // Si viene action=create, crear nuevo registro
            if ($request->has('action') && $request->action === 'create') {
                $validated = $request->validate([
                    'store_id' => 'required|exists:stores,id',
                    'amount' => 'required|numeric',
                ]);

                $entryData = [
                    'date' => $bankMovement->date,
                    'store_id' => $validated['store_id'],
                    'amount' => $validated['amount'],
                    'total_amount' => $validated['amount'],
                    'status' => 'pagado',
                    'paid_amount' => $validated['amount'],
                    'created_by' => Auth::id(),
                ];

                // Si es débito (gasto)
                if ($bankMovement->type === 'debit') {
                    $validated = $request->validate([
                        'store_id' => 'required|exists:stores,id',
                        'supplier_id' => 'nullable|exists:suppliers,id',
                        'expense_concept' => 'required|string|max:255',
                        'amount' => 'required|numeric',
                        'expense_category' => 'nullable|string|max:255',
                    ]);

                    $entryData['type'] = 'expense';
                    $entryData['supplier_id'] = $validated['supplier_id'] ?? null;
                    $entryData['expense_payment_method'] = 'bank';
                    $entryData['expense_amount'] = $validated['amount'];
                    $entryData['expense_category'] = $validated['expense_category'] ?? null;
                    $entryData['expense_source'] = 'conciliacion_bancaria';
                    $entryData['expense_concept'] = $validated['expense_concept'];
                    $entryData['concept'] = $validated['expense_concept'];
                } else {
                    // Si es crédito (ingreso)
                    $validated = $request->validate([
                        'store_id' => 'required|exists:stores,id',
                        'concept' => 'nullable|string|max:255',
                        'amount' => 'required|numeric|min:0.01',
                    ]);

                    $entryData['type'] = 'income';
                    $entryData['concept'] = $validated['concept'] ?? $bankMovement->description;
                }

                $entryData['notes'] = json_encode([
                    'source' => 'bank_movement',
                    'bank_movement_id' => $bankMovement->id,
                ]);

                $financialEntry = FinancialEntry::create($entryData);

                $bankMovement->update([
                    'is_conciliated' => true,
                    'financial_entry_id' => $financialEntry->id,
                ]);

                return redirect()->route('financial.bank-conciliation')
                    ->with('success', 'Registro financiero creado y movimiento conciliado correctamente.');
            }

            return back()->with('error', 'Acción no válida.');

        } catch (\Exception $e) {
            Log::error('Error conciliando movimiento bancario: '.$e->getMessage());

            return back()->with('error', 'Error al conciliar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Obtener ingresos disponibles para conciliación (AJAX).
     * Solo ingresos con método de pago banco (datáfono/TPV) del mes indicado.
     * Si el ingreso ya tiene gasto por la diferencia TPV creado, se devuelve con disabled=true (mostrar en gris, no seleccionable).
     */
    public function getAvailableIncomes(Request $request)
    {
        $storeId = $request->get('store_id');
        $month = $request->get('month'); // Y-m

        if (! $storeId || ! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return response()->json(['incomes' => []]);
        }

        $start = \Carbon\Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $entries = FinancialEntry::where('store_id', $storeId)
            ->where('type', 'income')
            ->where('expense_payment_method', 'bank')
            ->whereBetween('date', [$start, $end])
            ->whereNull('deleted_at')
            ->orderBy('date')
            ->orderBy('amount', 'desc')
            ->select('id', 'date', 'concept', 'amount', 'total_amount', 'income_concept')
            ->get();

        $incomeIds = $entries->pluck('id')->toArray();
        $idsWithTpvDiff = $incomeIds ? FinancialEntry::where('type', 'expense')
            ->whereIn('source_income_id', $incomeIds)
            ->pluck('source_income_id')
            ->flip()
            ->toArray() : [];

        $incomes = $entries->map(function ($entry) use ($idsWithTpvDiff) {
            $amt = (float) ($entry->total_amount ?? $entry->amount ?? 0);

            return [
                'id' => $entry->id,
                'date' => $entry->date->format('d/m/Y'),
                'concept' => $entry->income_concept ?? $entry->concept ?? 'Ingreso',
                'amount' => $amt,
                'disabled' => isset($idsWithTpvDiff[$entry->id]),
            ];
        });

        return response()->json(['incomes' => $incomes]);
    }

    /**
     * Enlazar movimiento bancario a gasto existente
     */
    public function linkBankMovement(Request $request, $id)
    {
        $validated = $request->validate([
            'financial_entry_id' => 'required|exists:financial_entries,id',
        ]);

        try {
            $bankMovement = BankMovement::findOrFail($id);
            $financialEntry = FinancialEntry::findOrFail($validated['financial_entry_id']);

            // Verificar que el movimiento no esté ya conciliado
            if ($bankMovement->is_conciliated) {
                return back()->with('error', 'Este movimiento ya está conciliado.');
            }

            $bankMovement->update([
                'is_conciliated' => true,
                'financial_entry_id' => $financialEntry->id,
            ]);

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario enlazado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error enlazando movimiento bancario: '.$e->getMessage());

            return back()->with('error', 'Error al enlazar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Enlazar gasto existente a movimiento bancario (nueva ruta)
     */
    public function linkExpenseFromBankMovement(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'financial_entry_id' => 'required|exists:financial_entries,id',
        ]);

        try {
            $financialEntry = FinancialEntry::findOrFail($validated['financial_entry_id']);

            // Verificar que el movimiento no esté ya conciliado
            if ($bankMovement->is_conciliated) {
                return back()->with('error', 'Este movimiento ya está conciliado.');
            }

            $bankMovement->update([
                'is_conciliated' => true,
                'financial_entry_id' => $financialEntry->id,
            ]);

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario enlazado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error enlazando movimiento bancario: '.$e->getMessage());

            return back()->with('error', 'Error al enlazar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Crear nuevo gasto desde movimiento bancario (nueva ruta)
     */
    public function createExpenseFromBankMovementRoute(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'expense_category' => 'nullable|string|max:255',
            'expense_concept' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        try {
            // Verificar que el movimiento no esté ya conciliado
            if ($bankMovement->is_conciliated) {
                return back()->with('error', 'Este movimiento ya está conciliado.');
            }

            // Crear registro financiero de gasto
            $financialEntry = FinancialEntry::create([
                'date' => $bankMovement->date,
                'store_id' => $validated['store_id'],
                'type' => 'expense',
                'expense_payment_method' => 'bank',
                'expense_amount' => $validated['amount'],
                'amount' => $validated['amount'],
                'total_amount' => $validated['amount'],
                'status' => 'pagado',
                'paid_amount' => $validated['amount'],
                'expense_category' => $validated['expense_category'] ?? null,
                'expense_source' => 'conciliacion_bancaria',
                'expense_concept' => $validated['expense_concept'],
                'concept' => $validated['expense_concept'],
                'notes' => json_encode([
                    'source' => 'bank_movement',
                    'bank_movement_id' => $bankMovement->id,
                ]),
                'created_by' => Auth::id(),
            ]);

            // Conciliar el movimiento bancario
            $bankMovement->update([
                'is_conciliated' => true,
                'financial_entry_id' => $financialEntry->id,
            ]);

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Gasto creado y movimiento conciliado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error creando gasto desde movimiento bancario: '.$e->getMessage());

            return back()->with('error', 'Error al crear el gasto: '.$e->getMessage());
        }
    }

    /**
     * Ignorar movimiento bancario (nueva ruta POST)
     */
    public function ignoreBankMovementRoute(BankMovement $bankMovement)
    {
        try {
            if ($bankMovement->is_conciliated) {
                return back()->with('error', 'Este movimiento ya está conciliado.');
            }

            // Marcar como conciliado pero sin enlazar a ningún registro financiero
            // Esto permite que no aparezca en los pendientes
            $bankMovement->update([
                'is_conciliated' => true,
                'financial_entry_id' => null,
            ]);

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario marcado como ignorado.');

        } catch (\Exception $e) {
            Log::error('Error ignorando movimiento bancario: '.$e->getMessage());

            return back()->with('error', 'Error al ignorar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Mostrar formulario de importación de movimientos bancarios
     */
    /**
     * Download CSV template for bank movements import
     */
    public function downloadBankImportTemplate()
    {
        $filename = 'plantilla_importacion_movimientos_bancarios.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Añadir BOM para UTF-8 (ayuda con Excel)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($file, ['Fecha', 'Descripción', 'Importe', 'Tipo'], ';');

            // Ejemplos de datos
            fputcsv($file, ['2024-01-15', 'Transferencia recibida', '1500.00', 'credit'], ';');
            fputcsv($file, ['2024-01-16', 'Pago proveedor', '-250.50', 'debit'], ';');
            fputcsv($file, ['2024-01-17', 'Nómina empleados', '-3200.00', 'debit'], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bankImportForm()
    {
        $this->syncStoresFromBusinesses();
        $bankAccounts = BankAccount::with('store')->orderBy('bank_name')->get();

        // Obtener errores de la sesión si existen
        $importErrors = session('errors', []);

        return view('financial.bank-import', compact('bankAccounts', 'importErrors'));
    }

    /**
     * Parsear fecha desde CSV aceptando formatos DD/MM/AAAA y DD-MM-AAAA
     */
    private function parseDateFromCsv($dateString)
    {
        $dateString = trim($dateString);

        if (empty($dateString)) {
            return null;
        }

        // Intentar formatos específicos primero: DD/MM/AAAA y DD-MM-AAAA
        $patterns = [
            '/^(\d{2})\/(\d{2})\/(\d{4})$/',  // DD/MM/AAAA
            '/^(\d{2})-(\d{2})-(\d{4})$/',     // DD-MM-AAAA
            '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/',  // D/M/AAAA o DD/MM/AAAA (flexible)
            '/^(\d{1,2})-(\d{1,2})-(\d{4})$/',     // D-M-AAAA o DD-MM-AAAA (flexible)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $dateString, $matches)) {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];

                // Validar que la fecha sea válida
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }

        // Si no coincide con los formatos específicos, intentar con Carbon como fallback
        try {
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Normalizar nombre de columna: eliminar BOM, trim, lowercase, quitar acentos
     */
    private function normalizeColumnName($name)
    {
        // Eliminar BOM UTF-8 si existe
        if (substr($name, 0, 3) === "\xEF\xBB\xBF") {
            $name = substr($name, 3);
        }

        // Trim
        $name = trim($name);

        // Convertir a minúsculas
        $name = mb_strtolower($name, 'UTF-8');

        // Normalizar acentos (transliterar a ASCII)
        // Mapeo manual de caracteres acentuados comunes en español
        $accents = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'Ñ' => 'n', 'Ü' => 'u',
        ];
        $name = strtr($name, $accents);

        // Intentar transliteración con iconv si está disponible
        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if ($transliterated !== false) {
                $name = $transliterated;
            }
        }

        // Eliminar caracteres no alfanuméricos excepto guiones bajos
        $name = preg_replace('/[^a-z0-9_]/', '', $name);

        return $name;
    }

    /**
     * Normalizar texto para comparación (lowercase, sin acentos, sin caracteres especiales)
     */
    private function normalizeTextForComparison($text)
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        // Normalizar acentos
        $accents = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ];
        $text = strtr($text, $accents);

        // Eliminar caracteres especiales excepto espacios y asteriscos
        $text = preg_replace('/[^a-z0-9\s*]/', ' ', $text);

        // Normalizar espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Extraer fecha del texto si existe
     */
    private function extractDateFromText($text)
    {
        // Buscar patrones de fecha comunes: DD/MM/YYYY, DD-MM-YYYY, etc.
        if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $text, $matches)) {
            try {
                $date = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[1]);

                return $date->format('d/m/Y');
            } catch (\Exception $e) {
                try {
                    $date = \Carbon\Carbon::createFromFormat('d-m-Y', $matches[1]);

                    return $date->format('d/m/Y');
                } catch (\Exception $e2) {
                    // Si no se puede parsear, devolver null
                }
            }
        }

        return null;
    }

    /**
     * Detectar si es un traspaso basado en el diccionario
     */
    private function detectTransfer($normalizedText)
    {
        $transferKeywords = ['traspaso', 'prestamo', 'devolucion', 'deposito efectivo'];

        foreach ($transferKeywords as $keyword) {
            if (strpos($normalizedText, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener concepto normalizado basado en el diccionario
     */
    private function getConceptFromDictionary($rawDescription, $date = null)
    {
        // Si no hay descripción original, usar concepto genérico como último recurso
        if (empty(trim($rawDescription))) {
            $concept = 'Movimiento bancario';
            if ($date) {
                $concept .= ' '.\Carbon\Carbon::parse($date)->format('d/m/Y');
            }

            return $concept;
        }

        $normalized = $this->normalizeTextForComparison($rawDescription);

        // Primero verificar si es traspaso
        if ($this->detectTransfer($normalized)) {
            $concept = 'TRASPASO';
            if (strpos($normalized, 'traspaso entre cuentas') !== false) {
                $concept = 'TRASPASO ENTRE CUENTAS';
            } elseif (strpos($normalized, 'ingreso efectivo') !== false || strpos($normalized, 'deposito efectivo') !== false) {
                $concept = 'INGRESO EFECTIVO';
            }

            // Añadir fecha si existe
            $extractedDate = $this->extractDateFromText($rawDescription);
            if ($extractedDate) {
                $concept .= ' '.$extractedDate;
            } elseif ($date) {
                $concept .= ' '.\Carbon\Carbon::parse($date)->format('d/m/Y');
            }

            return $concept;
        }

        // Diccionario de ingresos y gastos
        $dictionary = [
            // Ingresos
            [
                'keywords' => ['liquidacion', 'tpv'],
                'concept' => 'INGRESO DATÁFONO',
            ],

            // Gastos
            [
                'keywords' => ['repsol'],
                'concept' => 'Recibo Repsol, S.l.u.',
            ],
            [
                'keywords' => ['iberdrola'],
                'concept' => 'IBERDROLA CLIENTES, S.A.U',
            ],
            [
                'keywords' => ['o2', 'telefonica'],
                'concept' => 'RECIBO O2 FIBRA',
            ],
            [
                'keywords' => ['tgss', 'seguridad social'],
                'concept' => 'CUOTAS SEGURIDAD SOCIAL',
            ],
            [
                'keywords' => ['paypal', 'uber'],
                'concept' => 'UBERPAYMENT',
            ],
            [
                'keywords' => ['paypal', 'glovo'],
                'concept' => 'GLOVO',
            ],
            [
                'keywords' => ['nomina'],
                'concept' => 'PAGO DE NOMINAS',
            ],
            [
                'keywords' => ['shopify'],
                'concept' => 'SHOPIFY',
            ],
            [
                'keywords' => ['comision', 'tasa tpv'],
                'concept' => 'TASA TPV',
            ],
            [
                'keywords' => ['impuesto'],
                'concept' => 'IMPUESTO',
            ],
        ];

        // Verificar diccionario - SIEMPRE se evalúa antes de usar concepto genérico
        foreach ($dictionary as $entry) {
            $allKeywordsFound = true;
            foreach ($entry['keywords'] as $keyword) {
                if (strpos($normalized, $keyword) === false) {
                    $allKeywordsFound = false;
                    break;
                }
            }

            if ($allKeywordsFound) {
                // Si el diccionario detecta una coincidencia, usar el concepto corregido del diccionario
                return $entry['concept'];
            }
        }

        // Si el diccionario NO detecta coincidencia, usar la descripción original del banco
        // Limpiar la descripción original: extraer solo la parte principal antes de "Concepto:"
        $concept = $rawDescription;
        if (stripos($concept, 'Concepto:') !== false) {
            $parts = explode('Concepto:', $concept);
            $concept = trim($parts[0]);
        }

        // Limpiar espacios múltiples y caracteres al final
        $concept = trim(preg_replace('/\s+/', ' ', $concept));

        // Si después de limpiar queda vacío, usar concepto genérico como último recurso
        if (empty($concept)) {
            $concept = 'Movimiento bancario';
            if ($date) {
                $concept .= ' '.\Carbon\Carbon::parse($date)->format('d/m/Y');
            }
        }

        return $concept;
    }

    /**
     * Detectar el separador del CSV (',' o ';')
     */
    private function detectCsvDelimiter($filePath)
    {
        $handle = fopen($filePath, 'r');
        $firstLine = fgets($handle);
        fclose($handle);

        if (! $firstLine) {
            return ','; // Por defecto
        }

        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    /**
     * Procesar importación de movimientos bancarios desde CSV/Excel
     */
    public function bankImportStore(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // Máximo 10MB
        ]);

        try {
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            $imported = 0;
            $errors = [];

            if (in_array($extension, ['csv', 'txt'])) {
                $filePath = $file->getRealPath();

                // Detectar separador automáticamente
                $delimiter = $this->detectCsvDelimiter($filePath);

                // Procesar CSV
                $handle = fopen($filePath, 'r');

                // Leer encabezados (primera línea)
                $headers = fgetcsv($handle, 0, $delimiter);

                if (! $headers) {
                    fclose($handle);

                    return back()->withInput()->with('error', 'El archivo CSV está vacío o no es válido.');
                }

                // Guardar cabeceras originales para mostrar en errores
                $originalHeaders = $headers;

                // Normalizar encabezados
                $headers = array_map(function ($h) {
                    return $this->normalizeColumnName($h);
                }, $headers);

                // Buscar índices de columnas esperadas (con múltiples variantes)
                $dateIndex = false;
                $descriptionIndex = false;
                $amountIndex = false;
                $typeIndex = false;

                // Variantes para fecha
                $dateVariants = ['fecha', 'date', 'fechamovimiento', 'fechamov', 'fecha_movimiento'];
                foreach ($dateVariants as $variant) {
                    $dateIndex = array_search($variant, $headers);
                    if ($dateIndex !== false) {
                        break;
                    }
                }

                // Variantes para descripción
                $descriptionVariants = ['descripcion', 'description', 'concepto', 'concept', 'detalle', 'detail', 'motivo'];
                foreach ($descriptionVariants as $variant) {
                    $descriptionIndex = array_search($variant, $headers);
                    if ($descriptionIndex !== false) {
                        break;
                    }
                }

                // Variantes para importe
                $amountVariants = ['importe', 'amount', 'cantidad', 'quantity', 'monto', 'valor', 'value'];
                foreach ($amountVariants as $variant) {
                    $amountIndex = array_search($variant, $headers);
                    if ($amountIndex !== false) {
                        break;
                    }
                }

                // Variantes para tipo
                $typeVariants = ['tipo', 'type', 'tipomovimiento', 'movementtype'];
                foreach ($typeVariants as $variant) {
                    $typeIndex = array_search($variant, $headers);
                    if ($typeIndex !== false) {
                        break;
                    }
                }

                // Validar que tenga las columnas necesarias ANTES de procesar filas
                if ($dateIndex === false || $descriptionIndex === false || $amountIndex === false) {
                    fclose($handle);
                    $missingColumns = [];
                    if ($dateIndex === false) {
                        $missingColumns[] = 'Fecha';
                    }
                    if ($descriptionIndex === false) {
                        $missingColumns[] = 'Descripción';
                    }
                    if ($amountIndex === false) {
                        $missingColumns[] = 'Importe';
                    }

                    $foundHeaders = implode(', ', array_map(function ($h) {
                        return '"'.$h.'"';
                    }, $originalHeaders));

                    return back()->withInput()->with('error',
                        'No se encontraron las columnas requeridas: '.implode(', ', $missingColumns).'. '.
                        'Columnas encontradas en el archivo: '.$foundHeaders
                    );
                }

                $rowNumber = 1;
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rowNumber++;

                    // Obtener valores (usar el mismo delimitador)
                    $date = isset($row[$dateIndex]) ? trim($row[$dateIndex]) : null;
                    $description = isset($row[$descriptionIndex]) ? trim($row[$descriptionIndex]) : null;
                    $amount = isset($row[$amountIndex]) ? trim($row[$amountIndex]) : null;
                    $type = ($typeIndex !== false && isset($row[$typeIndex])) ? strtolower(trim($row[$typeIndex])) : null;

                    // Validar datos
                    if (empty($date) || empty($description) || empty($amount)) {
                        $errors[] = "Fila {$rowNumber}: Faltan datos requeridos";

                        continue;
                    }

                    // Parsear fecha (aceptar varios formatos: DD/MM/AAAA, DD-MM-AAAA, YYYY-MM-DD, etc.)
                    try {
                        $parsedDate = $this->parseDateFromCsv($date);
                        if (! $parsedDate) {
                            $errors[] = "Fila {$rowNumber}: Fecha inválida: {$date}";

                            continue;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Fila {$rowNumber}: Fecha inválida: {$date}";

                        continue;
                    }

                    // Validar y limpiar importe
                    // Manejar formato europeo (1.234,56) vs americano (1,234.56)
                    $amount = trim($amount);

                    // Si contiene coma y punto, asumimos que la coma es separador de miles si está antes del punto,
                    // o separador decimal si está después.
                    // Pero lo más común en España es 1.234,56 o 1234,56
                    if (strpos($amount, ',') !== false && strpos($amount, '.') !== false) {
                        if (strpos($amount, ',') > strpos($amount, '.')) {
                            // Formato 1.234,56 -> quitar puntos, cambiar coma por punto
                            $amount = str_replace('.', '', $amount);
                            $amount = str_replace(',', '.', $amount);
                        } else {
                            // Formato 1,234.56 -> quitar comas
                            $amount = str_replace(',', '', $amount);
                        }
                    } elseif (strpos($amount, ',') !== false) {
                        // Solo tiene coma: 57,77 o 1234,56 -> cambiar por punto
                        $amount = str_replace(',', '.', $amount);
                    }

                    $amount = str_replace(['€', '$'], '', $amount);
                    $amount = trim($amount);
                    $amountValue = (float) $amount; // Mantener el signo original
                    $isNegative = $amountValue < 0;
                    $amountPositive = abs($amountValue); // Valor absoluto para guardar

                    // Validar que el importe no sea cero
                    if ($amountPositive == 0) {
                        $errors[] = "Fila {$rowNumber}: El importe no puede ser cero";

                        continue;
                    }

                    // Guardar descripción original
                    $rawDescription = $description;

                    // Obtener concepto normalizado del diccionario
                    $normalizedConcept = $this->getConceptFromDictionary($rawDescription, $parsedDate);

                    // Detectar si es traspaso
                    $normalized = $this->normalizeTextForComparison($rawDescription);
                    $isTransfer = $this->detectTransfer($normalized);

                    // Determinar tipo según el signo y el tipo indicado en el CSV
                    $finalType = null;
                    $destinationStoreId = null;
                    $status = 'confirmado';

                    // Normalizar el tipo del CSV
                    $csvType = null;
                    if (! empty($type)) {
                        $csvType = in_array($type, ['credit', 'debit', 'credito', 'debito', 'c', 'd', 'transfer', 'traspaso', 't', 'gasto', 'expense', 'ingreso', 'income'])
                            ? strtolower($type)
                            : null;
                    }

                    // Si el CSV indica traspaso explícitamente, o se detecta como traspaso
                    if ($csvType === 'transfer' || $csvType === 'traspaso' || $csvType === 't' || $isTransfer) {
                        $finalType = 'transfer';
                        $status = 'pendiente';

                        if ($isNegative) {
                            // Traspaso SALIENTE: importe negativo
                            // La tienda del bank_account es la ORIGEN
                            // destination_store_id se elige manualmente después
                            $destinationStoreId = null;
                        } else {
                            // Traspaso ENTRANTE: importe positivo
                            // La tienda del bank_account es la DESTINO
                            // Necesitamos elegir la ORIGEN manualmente
                            // Usaremos destination_store_id para almacenar la tienda origen (aunque el nombre sea confuso)
                            $destinationStoreId = null; // Se elige manualmente en la edición
                        }
                    } else {
                        // No es traspaso: determinar si es gasto o ingreso según signo y tipo CSV
                        if ($isNegative) {
                            // Importe negativo
                            if ($csvType === 'debit' || $csvType === 'debito' || $csvType === 'd' || $csvType === 'gasto' || $csvType === 'expense') {
                                // Importe NEGATIVO + tipo GASTO → Registrar como GASTO (debit)
                                $finalType = 'debit';
                            } else {
                                // Por defecto, importe negativo es débito (gasto)
                                $finalType = 'debit';
                            }
                        } else {
                            // Importe positivo
                            if ($csvType === 'credit' || $csvType === 'credito' || $csvType === 'c' || $csvType === 'ingreso' || $csvType === 'income') {
                                // Importe POSITIVO + tipo INGRESO → Registrar como INGRESO (credit)
                                $finalType = 'credit';
                            } else {
                                // Por defecto, importe positivo es crédito (ingreso)
                                $finalType = 'credit';
                            }
                        }
                    }

                    // Si no se pudo determinar el tipo, usar el signo como guía
                    if (! $finalType) {
                        $finalType = $isNegative ? 'debit' : 'credit';
                    }

                    // Crear movimiento bancario
                    // IMPORTANTE: NO crear transfers al importar. Solo crear bank_movements en estado pendiente.
                    // Los transfers se crearán manualmente al conciliar desde la vista de conciliación bancaria.
                    // Guardar el signo original en raw_description para poder determinar la dirección al conciliar
                    $rawDescriptionWithSign = $rawDescription;
                    if ($finalType === 'transfer') {
                        // Guardar el signo original en raw_description para referencia
                        $signIndicator = $isNegative ? '[NEG]' : '[POS]';
                        $rawDescriptionWithSign = $signIndicator.' '.$rawDescription;
                    }

                    $bankMovement = BankMovement::create([
                        'bank_account_id' => $bankAccount->id,
                        'destination_store_id' => $destinationStoreId, // Para traspasos: NULL (se elige después)
                        'date' => $parsedDate,
                        'description' => $normalizedConcept,
                        'raw_description' => $rawDescriptionWithSign, // Incluye indicador de signo para transfers
                        'amount' => round($amountPositive, 2), // Siempre positivo
                        'type' => $finalType, // 'debit', 'credit', o 'transfer'
                        'is_conciliated' => false,
                        'status' => $status, // 'pendiente' para traspasos, 'confirmado' para otros
                        'financial_entry_id' => null,
                        'transfer_id' => null, // Se asignará al conciliar como traspaso
                    ]);

                    // Si es un traspaso, intentar detectar si existe un transfer relacionado
                    // basándose en importe, fecha (±1 día) y tiendas cruzadas
                    if ($finalType === 'transfer') {
                        $existingTransfer = $this->findExistingTransferForBankMovement(
                            $bankMovement,
                            $amountPositive,
                            $parsedDate,
                            $bankAccount->store_id,
                            $isNegative
                        );

                        if ($existingTransfer) {
                            // Enlazar el bank_movement al transfer existente: conciliado y no pendiente
                            $bankMovement->update([
                                'transfer_id' => $existingTransfer->id,
                                'is_conciliated' => true,
                                'status' => 'conciliado',
                            ]);
                            Log::info('BankMovement enlazado a Transfer existente', [
                                'bank_movement_id' => $bankMovement->id,
                                'transfer_id' => $existingTransfer->id,
                            ]);
                        }
                    }

                    // NO intentar conciliar automáticamente - se hará manualmente desde la vista de conciliación

                    $imported++;
                }

                fclose($handle);
            } else {
                // Para Excel, usar una librería como PhpSpreadsheet o Laravel Excel
                // Por ahora, mostrar error indicando que solo CSV está soportado
                return back()->withInput()->with('error', 'El formato Excel aún no está soportado. Por favor, exporta el archivo a CSV e intenta de nuevo.');
            }

            $message = "Se importaron {$imported} movimientos bancarios correctamente.";
            if (! empty($errors)) {
                $message .= ' Errores encontrados: '.implode('; ', array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= ' y '.(count($errors) - 10).' más.';
                }
            }

            return redirect()->route('financial.bank-import')
                ->with('success', $message)
                ->with('importErrors', $errors);

        } catch (\Exception $e) {
            Log::error('Error importando movimientos bancarios: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Error al importar el archivo: '.$e->getMessage());
        }
    }

    /**
     * Conciliar automáticamente todos los movimientos bancarios pendientes
     * Útil para ejecutar al listar movimientos
     */
    public function reconcileAllBankMovements(): void
    {
        $pendingMovements = BankMovement::forCurrentCompany()
            ->where('is_conciliated', false)
            ->with('bankAccount')
            ->get();

        $reconciled = 0;
        foreach ($pendingMovements as $movement) {
            $wasConciliated = $movement->is_conciliated;
            $this->reconcileBankMovement($movement);

            // Refrescar el modelo para ver si se concilió
            $movement->refresh();
            if ($movement->is_conciliated && ! $wasConciliated) {
                $reconciled++;
            }
        }

        Log::info('Conciliación automática completada', [
            'total_pending' => $pendingMovements->count(),
            'reconciled' => $reconciled,
        ]);
    }

    /**
     * Conciliar automáticamente un movimiento bancario con registros financieros
     * Busca coincidencias por fecha, importe y tienda
     */
    private function reconcileBankMovement(BankMovement $bankMovement): void
    {
        try {
            $bankAccount = $bankMovement->bankAccount;
            $storeId = $bankAccount->store_id;
            $date = $bankMovement->date;
            $amount = (float) $bankMovement->amount;

            // Determinar el tipo de registro financiero esperado según el tipo de movimiento bancario
            // credit (ingreso en banco) -> income
            // debit (salida del banco) -> expense
            $expectedType = $bankMovement->type === 'credit' ? 'income' : 'expense';

            // Buscar registros financieros con:
            // - misma fecha
            // - mismo importe (con tolerancia de 0.01 para redondeos)
            // - misma tienda
            // - tipo correcto (income para credit, expense para debit)
            $query = FinancialEntry::where('store_id', $storeId)
                ->where('date', $date)
                ->where('type', $expectedType);

            // Buscar por diferentes campos de importe según el tipo
            if ($expectedType === 'income') {
                $query->where(function ($q) use ($amount) {
                    $q->whereBetween('amount', [$amount - 0.01, $amount + 0.01])
                        ->orWhereBetween('total_amount', [$amount - 0.01, $amount + 0.01])
                        ->orWhereBetween('income_amount', [$amount - 0.01, $amount + 0.01]);
                });
            } else {
                // expense
                $query->where(function ($q) use ($amount) {
                    $q->whereBetween('amount', [$amount - 0.01, $amount + 0.01])
                        ->orWhereBetween('total_amount', [$amount - 0.01, $amount + 0.01])
                        ->orWhereBetween('expense_amount', [$amount - 0.01, $amount + 0.01]);
                });
            }

            $matches = $query->get();

            // Si hay exactamente una coincidencia, conciliar
            if ($matches->count() === 1) {
                $financialEntry = $matches->first();
                $bankMovement->update([
                    'is_conciliated' => true,
                    'financial_entry_id' => $financialEntry->id,
                ]);

                Log::info('Movimiento bancario conciliado automáticamente', [
                    'bank_movement_id' => $bankMovement->id,
                    'financial_entry_id' => $financialEntry->id,
                    'date' => $date,
                    'amount' => $amount,
                ]);
            } else {
                // Si hay múltiples coincidencias o ninguna, dejar pendiente
                Log::debug('Movimiento bancario no conciliado automáticamente', [
                    'bank_movement_id' => $bankMovement->id,
                    'matches_count' => $matches->count(),
                    'date' => $date,
                    'amount' => $amount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al conciliar movimiento bancario: '.$e->getMessage(), [
                'bank_movement_id' => $bankMovement->id,
            ]);
        }
    }

    protected function getAvailableStores()
    {
        return $this->storesForCurrentUser();
    }

    private function applyPeriodFilterToTransfer($query, $period, $request = null)
    {
        if ($request && $request->has('date_from') && $request->has('date_to') && $request->date_from && $request->date_to) {
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

    /**
     * Obtiene años distintos de una query de entradas (compatible MySQL y SQLite).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array<int>
     */
    private function getDistinctYearsFromEntries($query): array
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            return $query->clone()
                ->selectRaw('DISTINCT YEAR(date) as year')
                ->orderByDesc('year')
                ->pluck('year')
                ->filter()
                ->values()
                ->toArray();
        }

        return $query->clone()
            ->selectRaw("DISTINCT CAST(strftime('%Y', date) AS INTEGER) as year")
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values()
            ->toArray();
    }

    private function applyPeriodFilter($query, $period, $request = null)
    {
        if ($request && $request->has('date_from') && $request->has('date_to') && $request->date_from && $request->date_to) {
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

    /**
     * Crear o actualizar ingresos automáticos para un cierre diario.
     * Primero se eliminan todos los ingresos asociados a este cierre (evita duplicados al crear/editar).
     */
    private function syncDailyCloseIncomes(FinancialEntry $dailyClose)
    {
        if ($dailyClose->type !== 'daily_close') {
            return;
        }

        // Eliminar cualquier ingreso previo asociado a este cierre (mismo formato u otro) para no duplicar
        $this->deleteDailyCloseIncomes($dailyClose);

        // Calcular efectivo del cierre
        $cashCounted = $dailyClose->calculateCashTotal();
        $cashInitial = (float) ($dailyClose->cash_initial ?? 0);
        $cashExpenses = (float) ($dailyClose->cash_expenses ?? 0);
        $cashSales = round($cashCounted - $cashInitial + $cashExpenses, 2);

        // Datáfono
        $tpv = (float) ($dailyClose->tpv ?? 0);

        $userId = $dailyClose->created_by ?? Auth::id();
        $notes = 'daily_close_id:'.$dailyClose->id;

        $reportingMonth = $dailyClose->getReportingMonth();

        // Crear solo el ingreso de efectivo si aplica
        if ($cashSales > 0) {
            FinancialEntry::create([
                'type' => 'income',
                'store_id' => $dailyClose->store_id,
                'date' => $dailyClose->date,
                'reporting_month' => $reportingMonth,
                'amount' => $cashSales,
                'income_amount' => $cashSales,
                'income_category' => 'cierre_diario',
                'income_concept' => 'Ingreso efectivo cierre diario',
                'expense_payment_method' => 'cash',
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        }

        // Crear solo el ingreso de datáfono si aplica
        if ($tpv > 0) {
            FinancialEntry::create([
                'type' => 'income',
                'store_id' => $dailyClose->store_id,
                'date' => $dailyClose->date,
                'reporting_month' => $reportingMonth,
                'amount' => $tpv,
                'income_amount' => $tpv,
                'income_category' => 'cierre_diario',
                'income_concept' => 'Ingreso datáfono cierre diario',
                'expense_payment_method' => 'bank',
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Eliminar ingresos automáticos asociados a un cierre diario (cualquier formato de notes).
     */
    private function deleteDailyCloseIncomes(FinancialEntry $dailyClose)
    {
        if ($dailyClose->type !== 'daily_close') {
            return;
        }

        $id = (int) $dailyClose->id;
        // Incluir ambos formatos: "daily_close_id:X" (controller) y "Generado automáticamente desde cierre diario #X" (command)
        FinancialEntry::where('type', 'income')
            ->where('store_id', $dailyClose->store_id)
            ->where('date', $dailyClose->date)
            ->where(function ($q) use ($id) {
                $q->where('notes', 'LIKE', '%daily_close_id:'.$id.'%')
                    ->orWhere('notes', 'LIKE', '%Generado automáticamente desde cierre diario #'.$id.'%');
            })
            ->delete();
    }

    /**
     * Sincronizar gastos del cierre diario: crear un FinancialEntry tipo expense por cada expense_item
     * para que aparezcan en el apartado de gastos.
     */
    private function syncDailyCloseExpenses(FinancialEntry $dailyClose): void
    {
        if ($dailyClose->type !== 'daily_close') {
            return;
        }

        $id = (int) $dailyClose->id;
        // Eliminar gastos que estaban vinculados a este cierre (para volver a crearlos según expense_items actual)
        FinancialEntry::where('type', 'expense')
            ->where('expense_source', 'cierre_diario')
            ->where(function ($q) use ($id) {
                $q->where('notes', 'like', '%"daily_close_id":'.$id.',%')
                    ->orWhere('notes', 'like', '%"daily_close_id":'.$id.'}%');
            })
            ->delete();

        $expenseItems = $dailyClose->expense_items ?? [];
        if (! is_array($expenseItems)) {
            return;
        }

        $userId = $dailyClose->created_by ?? Auth::id();
        foreach ($expenseItems as $index => $item) {
            $amount = (float) ($item['amount'] ?? 0);
            if ($amount == 0) {
                continue;
            }
            $concept = $item['concept'] ?? $item['expense_concept'] ?? 'Gasto cierre diario';
            $category = $item['expense_category'] ?? 'otros';

            FinancialEntry::create([
                'date' => $dailyClose->date,
                'reporting_month' => $dailyClose->getReportingMonth(),
                'store_id' => $dailyClose->store_id,
                'supplier_id' => null,
                'type' => 'expense',
                'expense_amount' => $amount,
                'amount' => $amount,
                'total_amount' => $amount,
                'expense_category' => $category,
                'expense_source' => 'cierre_diario',
                'expense_concept' => $concept,
                'concept' => $concept,
                'expense_payment_method' => 'cash',
                'expense_paid_cash' => true,
                'status' => 'pagado',
                'paid_amount' => $amount,
                'notes' => json_encode([
                    'daily_close_id' => $dailyClose->id,
                    'item_index' => $index,
                ]),
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Eliminar gastos asociados a un cierre diario (los creados desde expense_items)
     */
    private function deleteDailyCloseExpenses(FinancialEntry $dailyClose): void
    {
        if ($dailyClose->type !== 'daily_close') {
            return;
        }

        $id = (int) $dailyClose->id;
        FinancialEntry::where('type', 'expense')
            ->where('expense_source', 'cierre_diario')
            ->where(function ($q) use ($id) {
                $q->where('notes', 'like', '%"daily_close_id":'.$id.',%')
                    ->orWhere('notes', 'like', '%"daily_close_id":'.$id.'}%');
            })
            ->delete();
    }

    /**
     * Mostrar formulario de edición de movimiento bancario
     */
    public function editBankMovement(BankMovement $bankMovement)
    {
        $bankMovement->load(['bankAccount.store', 'destinationStore']);
        $stores = $this->getAvailableStores();

        return view('financial.bank-movements.edit', compact('bankMovement', 'stores'));
    }

    /**
     * Actualizar movimiento bancario
     */
    public function updateBankMovement(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|in:credit,debit,transfer,expense,income',
            'destination_store_id' => 'nullable|exists:stores,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            // Normalizar tipos: expense -> debit, income -> credit (para compatibilidad)
            $type = $validated['type'];
            if ($type === 'expense') {
                $type = 'debit';
            } elseif ($type === 'income') {
                $type = 'credit';
            }

            // Determinar el estado según el tipo y si tiene tienda destino
            $status = 'confirmado';
            if ($type === 'transfer') {
                $status = $validated['destination_store_id'] ? 'pendiente' : 'confirmado';
            }

            $bankMovement->update([
                'description' => $validated['description'],
                'type' => $type,
                'destination_store_id' => $type === 'transfer' ? $validated['destination_store_id'] : null,
                'date' => $validated['date'],
                'amount' => round($validated['amount'], 2),
                'status' => $status,
            ]);

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Movimiento bancario actualizado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar el movimiento: '.$e->getMessage());
        }
    }

    /**
     * Confirmar traspaso bancario
     */
    public function confirmTransfer(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'destination_store_id' => 'required|exists:stores,id',
        ]);

        if ($bankMovement->type !== 'transfer') {
            return back()->with('error', 'Este movimiento no es un traspaso.');
        }

        try {
            $bankMovement->update([
                'destination_store_id' => $validated['destination_store_id'],
                'status' => 'confirmado',
            ]);

            return redirect()->route('financial.bank-conciliation')
                ->with('success', 'Traspaso confirmado correctamente. El saldo se ha ajustado en ambas tiendas.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al confirmar el traspaso: '.$e->getMessage());
        }
    }

    /**
     * Devuelve la configuración de cierre de caja de la empresa (nombres POS y si vales están activos).
     *
     * @param  int|null  $companyId  Si se pasa (p. ej. desde show), se usa esta empresa; si no, session('company_id')
     * @return array{pos_label: string, pos_cash_label: string, pos_card_label: string, vouchers_enabled: bool}
     */
    protected function getDailyCloseSettings(?int $companyId = null): array
    {
        $companyId = $companyId ?? session('company_id');
        if (! $companyId) {
            return [
                'pos_label' => 'Sistema POS',
                'pos_cash_label' => 'Sistema POS · Efectivo (€)',
                'pos_card_label' => 'Sistema POS · Tarjeta (€)',
                'vouchers_enabled' => true,
            ];
        }
        $company = Company::withoutGlobalScopes()->find($companyId);
        if (! $company) {
            return [
                'pos_label' => 'Sistema POS',
                'pos_cash_label' => 'Sistema POS · Efectivo (€)',
                'pos_card_label' => 'Sistema POS · Tarjeta (€)',
                'vouchers_enabled' => true,
            ];
        }

        return [
            'pos_label' => $company->daily_close_pos_label ?? 'Sistema POS',
            'pos_cash_label' => $company->daily_close_pos_cash_label ?? 'Sistema POS · Efectivo (€)',
            'pos_card_label' => $company->daily_close_pos_card_label ?? 'Sistema POS · Tarjeta (€)',
            'vouchers_enabled' => (bool) ($company->daily_close_vouchers_enabled ?? true),
        ];
    }
}
