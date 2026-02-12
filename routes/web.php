<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashWalletController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySelectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeclaredSalesController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreCashReductionController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\MonthlyObjectiveController;
use App\Http\Controllers\ModuleSettingsController;
use App\Http\Controllers\MonthlyObjectiveSettingController;
use App\Http\Controllers\RingInventoryController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\OvertimeSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacationController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// Rutas de autenticación sin middleware de empresa (para super_admin)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Selección de empresa (solo super_admin)
    Route::get('/company/select', [CompanySelectController::class, 'index'])->name('company.select');
    Route::post('/company/switch', [CompanySelectController::class, 'switch'])->name('company.switch');
    Route::post('/company/store', [CompanySelectController::class, 'store'])->name('company.store');
    Route::post('/company/exit', [CompanySelectController::class, 'exit'])->name('company.exit');
});

// Rutas que requieren empresa seleccionada
Route::middleware(['auth', 'company'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/widgets', [DashboardController::class, 'getWidgetLayout'])->name('dashboard.widgets.index');
    Route::post('/dashboard/widgets', [DashboardController::class, 'storeWidgetLayout'])->name('dashboard.widgets.store');
    Route::post('/dashboard/widgets/reset', [DashboardController::class, 'resetWidgetLayout'])->name('dashboard.widgets.reset');
    
    // Usuarios
    Route::resource('users', UserController::class);
    
    // Empleados (quick-user antes del resource para que no coincida con {employee})
    Route::post('employees/quick-user', [EmployeeController::class, 'storeQuickUser'])->name('employees.quick-user');
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{employee}/payrolls', [EmployeeController::class, 'uploadPayroll'])->name('employees.payrolls');
    Route::post('employees/payrolls/upload', [EmployeeController::class, 'uploadPayrollAuto'])->name('employees.payrolls.upload');
    
    // Nóminas
    Route::get('payrolls/{payroll}/view', [\App\Http\Controllers\PayrollController::class, 'view'])->name('payrolls.view');
    
    // Pedidos (vista principal = listado de proveedores; segundo nivel = pedidos del proveedor)
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/supplier/{supplier}', [OrderController::class, 'supplierOrders'])->name('orders.supplier');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // Proveedores
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);

    // Clientes: Pedidos clientes y Reparaciones (rutas independientes por submódulo)
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', function () {
            if (auth()->user()->hasPermission('clients.orders.view')) {
                return redirect()->route('clients.orders.index');
            }
            if (auth()->user()->hasPermission('clients.repairs.view')) {
                return redirect()->route('clients.repairs.index');
            }
            abort(403);
        })->name('index');

        Route::prefix('orders')->name('orders.')->middleware('permission:clients.orders.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\CustomerOrderController::class, 'index'])->name('index');
            Route::put('{customer_order}/status', [\App\Http\Controllers\CustomerOrderController::class, 'updateStatus'])->name('status')->middleware('permission:clients.orders.edit');
            Route::get('{store}/create', [\App\Http\Controllers\CustomerOrderController::class, 'create'])->name('create')->middleware('permission:clients.orders.create');
            Route::post('{store}', [\App\Http\Controllers\CustomerOrderController::class, 'store'])->name('store.post')->middleware('permission:clients.orders.create');
            Route::get('{store}/{customer_order}/edit', [\App\Http\Controllers\CustomerOrderController::class, 'edit'])->name('edit')->middleware('permission:clients.orders.edit');
            Route::put('{store}/{customer_order}', [\App\Http\Controllers\CustomerOrderController::class, 'update'])->name('update')->middleware('permission:clients.orders.edit');
            Route::delete('{store}/{customer_order}', [\App\Http\Controllers\CustomerOrderController::class, 'destroy'])->name('destroy')->middleware('permission:clients.orders.delete');
            Route::get('{store}', [\App\Http\Controllers\CustomerOrderController::class, 'storeIndex'])->name('store');
        });
        Route::prefix('repairs')->name('repairs.')->middleware('permission:clients.repairs.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\CustomerRepairController::class, 'index'])->name('index');
            Route::put('{customer_repair}/status', [\App\Http\Controllers\CustomerRepairController::class, 'updateStatus'])->name('status')->middleware('permission:clients.repairs.edit');
            Route::get('{store}/create', [\App\Http\Controllers\CustomerRepairController::class, 'create'])->name('create')->middleware('permission:clients.repairs.create');
            Route::post('{store}', [\App\Http\Controllers\CustomerRepairController::class, 'store'])->name('store.post')->middleware('permission:clients.repairs.create');
            Route::get('{store}/{customer_repair}/edit', [\App\Http\Controllers\CustomerRepairController::class, 'edit'])->name('edit')->middleware('permission:clients.repairs.edit');
            Route::put('{store}/{customer_repair}', [\App\Http\Controllers\CustomerRepairController::class, 'update'])->name('update')->middleware('permission:clients.repairs.edit');
            Route::delete('{store}/{customer_repair}', [\App\Http\Controllers\CustomerRepairController::class, 'destroy'])->name('destroy')->middleware('permission:clients.repairs.delete');
            Route::get('{store}', [\App\Http\Controllers\CustomerRepairController::class, 'storeIndex'])->name('store');
        });
    });

    // Empresa
    Route::get('/company', [CompanyController::class, 'show'])->name('company.show');
    Route::put('/company', [CompanyController::class, 'update'])->name('company.update');
    Route::post('/company/businesses', [CompanyController::class, 'storeBusiness'])->name('company.businesses.store');
    Route::put('/company/businesses/{business}', [CompanyController::class, 'updateBusiness'])->name('company.businesses.update');
    Route::delete('/company/businesses/{business}', [CompanyController::class, 'destroyBusiness'])->name('company.businesses.destroy');
    
    // Tiendas
    Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
    Route::post('/stores/{store}/bank-accounts', [StoreController::class, 'storeBankAccount'])->name('stores.bank-accounts.store');
    Route::delete('/bank-accounts/{bankAccount}', [StoreController::class, 'destroyBankAccount'])->name('bank-accounts.destroy');
    
    // Registros financieros - Rutas específicas ANTES del resource para evitar conflictos
    Route::get('/financial/export', [FinancialController::class, 'export'])->name('financial.export');
    Route::get('/financial/cash-control', [FinancialController::class, 'cashControl'])->name('financial.cash-control');
    Route::get('/financial/bank-control', [FinancialController::class, 'bankControl'])->name('financial.bank-control');
    Route::get('/financial/bank-import', [FinancialController::class, 'bankImportForm'])->name('financial.bank-import');
    Route::get('/financial/bank-import/template', [FinancialController::class, 'downloadBankImportTemplate'])->name('financial.bank-import.template');
    Route::post('/financial/bank-import', [FinancialController::class, 'bankImportStore'])->name('financial.bank-import.store');
    Route::get('/financial/bank-conciliation', [FinancialController::class, 'bankConciliation'])->name('financial.bank-conciliation');
    Route::get('/financial/bank-movements/{bankMovement}/edit', [FinancialController::class, 'editBankMovement'])->name('financial.bank-movements.edit');
    Route::put('/financial/bank-movements/{bankMovement}', [FinancialController::class, 'updateBankMovement'])->name('financial.bank-movements.update');
    Route::post('/financial/bank-movements/{bankMovement}/confirm-transfer', [FinancialController::class, 'confirmTransfer'])->name('financial.bank-movements.confirm-transfer');
    Route::post('/financial/bank-conciliation/{bankMovement}/link-expense', [FinancialController::class, 'linkBankMovementToExpense'])->name('financial.bank-conciliation.link-expense');
    Route::post('/financial/bank-conciliation/{bankMovement}/create-expense', [FinancialController::class, 'createExpenseFromBankMovement'])->name('financial.bank-conciliation.create-expense');
    Route::post('/financial/bank-conciliation/{bankMovement}/conciliate-transfer', [FinancialController::class, 'conciliateAsTransfer'])->name('financial.bank-conciliation.conciliate-transfer');
    Route::post('/financial/bank-conciliation/{bankMovement}/ignore', [FinancialController::class, 'ignoreBankMovement'])->name('financial.bank-conciliation.ignore');
    Route::delete('/financial/bank-conciliation/{bankMovement}', [FinancialController::class, 'destroyBankMovement'])->name('financial.bank-conciliation.destroy');
    Route::get('/financial/bank-movements/available-expenses', [FinancialController::class, 'getAvailableExpenses'])->name('financial.bank-movements.available-expenses');
    Route::get('/financial/bank-movements/available-incomes', [FinancialController::class, 'getAvailableIncomes'])->name('financial.bank-movements.available-incomes');
    Route::put('/financial/bank-movements/{id}/conciliate', [FinancialController::class, 'conciliateBankMovement'])->name('financial.bank-movement.conciliate');
    Route::put('/financial/bank-movements/{id}/link', [FinancialController::class, 'linkBankMovement'])->name('financial.bank-movement.link');
    Route::post('/financial/bank-movements/create-expense', [FinancialController::class, 'createExpenseFromBankMovement'])->name('financial.bank-movement.create-expense');
    Route::put('/financial/bank-movements/{id}/ignore', [FinancialController::class, 'ignoreBankMovement'])->name('financial.bank-movement.ignore');
    Route::get('/financial/cash-control/{store}', [FinancialController::class, 'cashControlStore'])->name('financial.cash-control-store');
    Route::get('/financial/cash-control/{store}/{month}', [FinancialController::class, 'cashControlMonth'])->name('financial.cash-control-month');
    Route::post('/financial/cash-control/{store}/{month}/expense', [FinancialController::class, 'storeCashControlExpense'])->name('financial.cash-control-expense');
    Route::post('/financial/entry/{entry}/cash-real', [FinancialController::class, 'updateCashReal'])->name('financial.update-cash-real');
    Route::get('/financial/add-cash-real-column', function() {
        try {
            \Illuminate\Support\Facades\Schema::table('financial_entries', function (\Illuminate\Database\Schema\Blueprint $table) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('financial_entries', 'cash_real')) {
                    $table->decimal('cash_real', 10, 2)->nullable()->after('cash_expenses');
                }
            });
            return redirect()->back()->with('success', 'Columna cash_real añadida correctamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    })->name('financial.add-cash-real-column');
    Route::get('/financial/income', [FinancialController::class, 'income'])->name('financial.income');
    Route::get('/financial/expenses', [FinancialController::class, 'expenses'])->name('financial.expenses');
    Route::get('/financial/daily-closes', [FinancialController::class, 'dailyCloses'])->name('financial.daily-closes');
    Route::get('/financial/trash', [FinancialController::class, 'trash'])->name('financial.trash');
    Route::post('/financial/restore/{id}', [FinancialController::class, 'restore'])->name('financial.restore');
    Route::delete('/financial/force-delete/{id}', [FinancialController::class, 'forceDelete'])->name('financial.force-delete');
    Route::post('/financial/empty-trash', [FinancialController::class, 'emptyTrash'])->name('financial.empty-trash');
    Route::post('/financial/generate-daily-close-entries', [FinancialController::class, 'generateDailyCloseEntries'])->name('financial.generate-daily-close-entries');
    
    // Recogida de efectivo
    Route::get('/financial/cash-withdrawals/create', [FinancialController::class, 'createCashWithdrawal'])->name('financial.cash-withdrawals.create');
    Route::post('/financial/cash-withdrawals', [FinancialController::class, 'storeCashWithdrawal'])->name('financial.cash-withdrawals.store');
    Route::post('/financial/cash-deposits', [FinancialController::class, 'storeCashDeposit'])->name('financial.cash-deposits.store');
    
    Route::resource('financial', FinancialController::class);
    
    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::post('/roles/{role}/reset-permissions', [RoleController::class, 'resetPermissions'])->name('roles.reset-permissions');
    
    // Facturas
    Route::get('/invoices/upload', [InvoiceController::class, 'upload'])->name('invoices.upload');
    Route::post('/invoices/upload', [InvoiceController::class, 'storeFromUpload'])->name('invoices.upload.store');
    Route::get('/invoices/clear-upload-session', [InvoiceController::class, 'clearUploadSession'])->name('invoices.clear-upload-session');
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('/invoices/{invoice}/serve', [InvoiceController::class, 'serve'])->name('invoices.serve');
    
    // Ventas Declaradas
    Route::get('/declared-sales', [DeclaredSalesController::class, 'index'])->name('declared-sales.index');
    Route::post('/declared-sales/generate-from-daily-closes', [DeclaredSalesController::class, 'generateFromDailyCloses'])->name('declared-sales.generate-from-daily-closes');
    
    // Configuración - Reducción de Efectivo por Tienda
    Route::get('/settings/cash-reductions', [StoreCashReductionController::class, 'index'])->name('store-cash-reductions.index');
    Route::put('/settings/cash-reductions', [StoreCashReductionController::class, 'update'])->name('store-cash-reductions.update');

    // Ajustes - Módulos (solo SuperAdmin)
    Route::get('/settings/modules', [ModuleSettingsController::class, 'index'])->name('module-settings.index');
    Route::put('/settings/modules', [ModuleSettingsController::class, 'update'])->name('module-settings.update');

    // Objetivos mensuales
    Route::get('/objectives', [MonthlyObjectiveController::class, 'index'])->name('objectives.index');
    Route::get('/objectives/import', [MonthlyObjectiveController::class, 'importForm'])->name('objectives.import');
    Route::get('/objectives/import/template', [MonthlyObjectiveController::class, 'downloadTemplate'])->name('objectives.import.template');
    Route::post('/objectives/import', [MonthlyObjectiveController::class, 'processImport'])->name('objectives.import.process');
    Route::get('/objectives/store/{store}/{year}', [MonthlyObjectiveController::class, 'storeMonths'])->name('objectives.store-months');
    Route::get('/objectives/store/{store}/{year}/{month}', [MonthlyObjectiveController::class, 'monthDetail'])->name('objectives.month');
    Route::put('/objectives/store/{store}/{year}/{month}/bases', [MonthlyObjectiveController::class, 'updateMonthBases'])->name('objectives.update-month-bases');
    Route::put('/objectives/rows/{objectiveDailyRow}', [MonthlyObjectiveController::class, 'updateBase'])->name('objectives.update-base');

    // Ajustes - Objetivos de ventas (porcentajes por mes)
    Route::get('/settings/objectives', [MonthlyObjectiveSettingController::class, 'index'])->name('objectives-settings.index');
    Route::post('/settings/objectives', [MonthlyObjectiveSettingController::class, 'store'])->name('objectives-settings.store');
    Route::delete('/settings/objectives/{objectives_setting}', [MonthlyObjectiveSettingController::class, 'destroy'])->name('objectives-settings.destroy');

    // Horas extras
    Route::get('/overtime', [OvertimeController::class, 'index'])->name('overtime.index');
    Route::get('/overtime/store/{store}/{year}', [OvertimeController::class, 'storeMonths'])->name('overtime.store-months');
    Route::get('/overtime/store/{store}/{year}/{month}', [OvertimeController::class, 'monthDetail'])->name('overtime.month');
    Route::get('/overtime/store/{store}/{year}/{month}/create', [OvertimeController::class, 'create'])->name('overtime.create');
    Route::post('/overtime/store/{store}/{year}/{month}', [OvertimeController::class, 'store'])->name('overtime.store');
    Route::get('/overtime/employee/{employee}', [OvertimeController::class, 'employeeDetail'])->name('overtime.employee');
    Route::get('/overtime/records/{overtimeRecord}/edit', [OvertimeController::class, 'editRecord'])->name('overtime.records.edit');
    Route::put('/overtime/records/{overtimeRecord}', [OvertimeController::class, 'updateRecord'])->name('overtime.records.update');
    Route::delete('/overtime/records/{overtimeRecord}', [OvertimeController::class, 'destroyRecord'])->name('overtime.records.destroy');

    // Vacaciones
    Route::get('/vacations', [VacationController::class, 'index'])->name('vacations.index');
    Route::get('/vacations/store/{store}/{year}', [VacationController::class, 'storeView'])->name('vacations.store');
    Route::get('/vacations/store/{store}/{year}/calendar', [VacationController::class, 'calendarMonths'])->name('vacations.calendar-months');
    Route::get('/vacations/store/{store}/{year}/{month}', [VacationController::class, 'calendarMonth'])->name('vacations.calendar-month');
    Route::put('/vacations/store/{store}/{year}/periods', [VacationController::class, 'updatePeriods'])->name('vacations.update-periods');
    Route::post('/vacations/days', [VacationController::class, 'toggleDay'])->name('vacations.toggle-day');
    Route::post('/vacations/days/bulk', [VacationController::class, 'registerWeeks'])->name('vacations.register-weeks');

    // Ajustes - Horas extras (precios por empleada)
    Route::get('/settings/overtime', [OvertimeSettingController::class, 'index'])->name('overtime-settings.index');
    Route::put('/settings/overtime', [OvertimeSettingController::class, 'update'])->name('overtime-settings.update');

    // Ajustes - Productos
    Route::get('/settings/products', [\App\Http\Controllers\ProductSettingsController::class, 'index'])->name('product-settings.index');
    Route::get('/settings/products/create', [\App\Http\Controllers\ProductSettingsController::class, 'create'])->name('product-settings.create');
    Route::post('/settings/products', [\App\Http\Controllers\ProductSettingsController::class, 'store'])->name('product-settings.store');
    Route::get('/settings/products/{product}/edit', [\App\Http\Controllers\ProductSettingsController::class, 'edit'])->name('product-settings.edit');
    Route::put('/settings/products/{product}', [\App\Http\Controllers\ProductSettingsController::class, 'update'])->name('product-settings.update');
    Route::delete('/settings/products/{product}', [\App\Http\Controllers\ProductSettingsController::class, 'destroy'])->name('product-settings.destroy');
    Route::post('/settings/products/categories', [\App\Http\Controllers\ProductSettingsController::class, 'storeCategory'])->name('product-settings.categories.store');

    // Ajustes - Inventarios (solo toggle anillos)
    Route::get('/settings/inventories', [\App\Http\Controllers\InventorySettingsController::class, 'index'])->name('inventory-settings.index');
    Route::post('/settings/inventories/toggle-rings', [\App\Http\Controllers\InventorySettingsController::class, 'toggleRings'])->name('inventory-settings.toggle-rings');
    
    // Carteras de Efectivo
    Route::get('/cash-wallets', [CashWalletController::class, 'index'])->name('cash-wallets.index');
    Route::get('/cash-wallets/create', [CashWalletController::class, 'create'])->name('cash-wallets.create');
    Route::post('/cash-wallets', [CashWalletController::class, 'store'])->name('cash-wallets.store');
    Route::get('/cash-wallets/{cashWallet}', [CashWalletController::class, 'show'])->name('cash-wallets.show');
    Route::get('/cash-wallets/{cashWallet}/edit', [CashWalletController::class, 'edit'])->name('cash-wallets.edit');
    Route::put('/cash-wallets/{cashWallet}', [CashWalletController::class, 'update'])->name('cash-wallets.update');
    Route::delete('/cash-wallets/{cashWallet}', [CashWalletController::class, 'destroy'])->name('cash-wallets.destroy');
    Route::post('/cash-wallets/{cashWallet}/expense', [CashWalletController::class, 'storeExpense'])->name('cash-wallets.expense');
    Route::get('/cash-wallets/{cashWallet}/expenses/{expense}/edit', [CashWalletController::class, 'editExpense'])->name('cash-wallets.expenses.edit');
    Route::put('/cash-wallets/{cashWallet}/expenses/{expense}', [CashWalletController::class, 'updateExpense'])->name('cash-wallets.expenses.update');
    Route::delete('/cash-wallets/{cashWallet}/expenses/{expense}', [CashWalletController::class, 'destroyExpense'])->name('cash-wallets.expenses.destroy');
    Route::post('/cash-wallets/{cashWallet}/transfer', [CashWalletController::class, 'storeTransfer'])->name('cash-wallets.transfer');
    Route::get('/cash-wallets/{cashWallet}/transfers/{transfer}/edit', [CashWalletController::class, 'editTransfer'])->name('cash-wallets.transfers.edit');
    Route::put('/cash-wallets/{cashWallet}/transfers/{transfer}', [CashWalletController::class, 'updateTransfer'])->name('cash-wallets.transfers.update');
    Route::delete('/cash-wallets/{cashWallet}/transfers/{transfer}', [CashWalletController::class, 'destroyTransfer'])->name('cash-wallets.transfers.destroy');
    Route::get('/cash-wallets/{cashWallet}/withdrawals/{withdrawal}/edit', [CashWalletController::class, 'editWithdrawal'])->name('cash-wallets.withdrawals.edit');
    Route::put('/cash-wallets/{cashWallet}/withdrawals/{withdrawal}', [CashWalletController::class, 'updateWithdrawal'])->name('cash-wallets.withdrawals.update');
    Route::delete('/cash-wallets/{cashWallet}/withdrawals/{withdrawal}', [CashWalletController::class, 'destroyWithdrawal'])->name('cash-wallets.withdrawals.destroy');
    
    // Traspasos
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}/edit', [TransferController::class, 'edit'])->name('transfers.edit');
    Route::put('/transfers/{transfer}', [TransferController::class, 'update'])->name('transfers.update');
    Route::delete('/transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');

    // Inventarios por categoría
    Route::get('/inventory/categories', [\App\Http\Controllers\CategoryInventoryController::class, 'categories'])->name('inventory.categories.index');
    Route::get('/inventory/categories/{category}/inventories', [\App\Http\Controllers\CategoryInventoryController::class, 'inventories'])->name('inventory.categories.inventories');
    Route::get('/inventory/categories/{category}/inventories/create', [\App\Http\Controllers\CategoryInventoryController::class, 'create'])->name('inventory.categories.create');
    Route::post('/inventory/categories/{category}/inventories', [\App\Http\Controllers\CategoryInventoryController::class, 'store'])->name('inventory.categories.store');
    Route::get('/inventory/categories/{category}/inventories/{inventory}', [\App\Http\Controllers\CategoryInventoryController::class, 'show'])->name('inventory.categories.show');
    Route::put('/inventory/categories/{category}/inventories/{inventory}', [\App\Http\Controllers\CategoryInventoryController::class, 'update'])->name('inventory.categories.update');
    Route::post('/inventory/categories/{category}/inventories/{inventory}/add-purchase', [\App\Http\Controllers\CategoryInventoryController::class, 'addPurchase'])->name('inventory.categories.add-purchase');

    // Ventas de productos
    Route::get('/inventory/sales', [\App\Http\Controllers\InventorySalesController::class, 'index'])->name('inventory.sales.index');
    Route::post('/inventory/sales', [\App\Http\Controllers\InventorySalesController::class, 'store'])->name('inventory.sales.store');
    Route::get('/inventory/sales/inventories', [\App\Http\Controllers\InventorySalesController::class, 'getInventories'])->name('inventory.sales.inventories');

    // Inventario de anillos (módulo Inventario) - requiere rings_inventory_enabled
    Route::middleware('rings.enabled')->group(function () {
        Route::get('/inventory/ring-inventories', [RingInventoryController::class, 'index'])->name('ring-inventories.index');
        Route::get('/inventory/ring-inventories/create', [RingInventoryController::class, 'create'])->name('ring-inventories.create');
        Route::post('/inventory/ring-inventories', [RingInventoryController::class, 'store'])->name('ring-inventories.store');
        Route::get('/inventory/ring-inventories/store/{store}/{year}', [RingInventoryController::class, 'storeMonths'])->name('ring-inventories.store-months');
        Route::get('/inventory/ring-inventories/store/{store}/{year}/{month}', [RingInventoryController::class, 'monthRecords'])->name('ring-inventories.month');
        Route::get('/inventory/ring-inventories/{ringInventory}', [RingInventoryController::class, 'show'])->name('ring-inventories.show');
        Route::get('/inventory/ring-inventories/{ringInventory}/edit', [RingInventoryController::class, 'edit'])->name('ring-inventories.edit');
        Route::put('/inventory/ring-inventories/{ringInventory}', [RingInventoryController::class, 'update'])->name('ring-inventories.update');
        Route::delete('/inventory/ring-inventories/{ringInventory}', [RingInventoryController::class, 'destroy'])->name('ring-inventories.destroy');
    });
});
