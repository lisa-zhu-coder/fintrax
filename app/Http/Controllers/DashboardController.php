<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\CashWallet;
use App\Models\DashboardWidget;
use App\Models\FinancialEntry;
use App\Models\Order;
use App\Models\OvertimeRecord;
use App\Models\OvertimeSetting;
use App\Models\Store;
use App\Support\WidgetRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:dashboard.main.view')->only(['index', 'getWidgetLayout', 'storeWidgetLayout', 'resetWidgetLayout']);
    }

    public function index(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $user = Auth::user();
        $stores = $this->storesForCurrentUser();

        $period = $request->get('period', 'this_month');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $selectedStore = $request->get('store', 'all');
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $enforcedStoreId = $user->getEnforcedStoreId();
            if ($enforcedStoreId !== null) {
                $selectedStore = (string) $enforcedStoreId;
            } elseif ($selectedStore !== 'all' && !$user->canAccessStore((int) $selectedStore)) {
                $selectedStore = 'all';
            }
        }

        $entries = $this->getFilteredEntries($selectedStore, $period, $user, $fromDate, $toDate);

        $summary = $this->calculateSummary($entries);
        $chartData = $this->prepareChartData($entries);
        $expensesByCategory = $this->getExpensesByCategory($selectedStore, $period, $user, $fromDate, $toDate);
        $incomeByPaymentMethod = $this->getIncomeByPaymentMethod($selectedStore, $period, $user, $fromDate, $toDate);
        $ordersPaidVsPending = $this->getOrdersPaidVsPending($selectedStore, $period, $user, $fromDate, $toDate);
        $overtimeByStore = $this->getOvertimeByStore($selectedStore, $period, $user, $fromDate, $toDate);
        $cashWallets = CashWallet::orderBy('name')->get();

        $widgetLayout = $this->getWidgetLayoutForUser($user);
        $availableWidgetKeys = WidgetRegistry::getAvailableKeys($user);

        return view('dashboard.index', compact('stores', 'selectedStore', 'period', 'fromDate', 'toDate', 'entries', 'summary', 'chartData', 'expensesByCategory', 'incomeByPaymentMethod', 'ordersPaidVsPending', 'overtimeByStore', 'cashWallets', 'widgetLayout', 'availableWidgetKeys'));
    }

    private function getFilteredEntries($selectedStore, $period, $user, $fromDate = null, $toDate = null)
    {
        $query = FinancialEntry::with(['store', 'creator']);

        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($selectedStore !== 'all' && $user->canAccessStore((int) $selectedStore)) {
            $query->where('store_id', $selectedStore);
        } else {
            $allowed = $user->getAllowedStoreIds();
            if (!empty($allowed)) {
                $query->whereIn('store_id', $allowed);
            }
        }

        $this->applyPeriodFilter($query, $period, $fromDate, $toDate);

        return $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();
    }

    private function getDateRangeForPeriod($period, $fromDate = null, $toDate = null): array
    {
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
            case 'last_year':
                $start = now()->subYear()->startOfYear();
                $end = now()->subYear()->endOfYear();
                break;
            case 'custom':
                if ($fromDate && $toDate) {
                    $start = \Carbon\Carbon::parse($fromDate)->startOfDay();
                    $end = \Carbon\Carbon::parse($toDate)->endOfDay();
                } else {
                    $start = now()->startOfMonth();
                }
                break;
            default:
                $start = now()->startOfMonth();
        }

        return [$start, $end];
    }

    private function applyPeriodFilter($query, $period, $fromDate = null, $toDate = null)
    {
        [$start, $end] = $this->getDateRangeForPeriod($period, $fromDate, $toDate);
        $query->whereBetween('date', [$start, $end]);
    }

    private function calculateSummary($entries)
    {
        $summary = [
            'total_income' => 0,
            'total_expenses' => 0,
            'total_refunds' => 0,
            'net' => 0,
            'daily_closes' => 0,
        ];

        foreach ($entries as $entry) {
            switch ($entry->type) {
                case 'income':
                    $summary['total_income'] += $entry->amount;
                    break;
                case 'expense':
                    $summary['total_expenses'] += $entry->amount;
                    break;
                case 'expense_refund':
                    $summary['total_refunds'] += $entry->amount;
                    break;
                case 'daily_close':
                    $summary['daily_closes']++;
                    break;
            }
        }

        $summary['net'] = $summary['total_income'] - $summary['total_expenses'] + $summary['total_refunds'];

        return $summary;
    }

    private function prepareChartData($entries)
    {
        $salesByDate = [];
        $expensesByDate = [];
        
        foreach ($entries as $entry) {
            $date = $entry->date->format('Y-m-d');
            
            if ($entry->type === 'daily_close') {
                $sales = (float) ($entry->sales ?? $entry->amount ?? 0);
                $expenses = (float) ($entry->expenses ?? 0);
            } elseif ($entry->type === 'income') {
                $sales = (float) ($entry->income_amount ?? $entry->amount ?? 0);
                $expenses = 0;
            } elseif ($entry->type === 'expense') {
                $sales = 0;
                $expenses = (float) ($entry->expense_amount ?? $entry->amount ?? 0);
            } elseif ($entry->type === 'expense_refund') {
                // Devoluciones: no se muestran en el gráfico del Dashboard
                $sales = 0;
                $expenses = 0;
            } else {
                $sales = 0;
                $expenses = 0;
            }
            
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
                $expensesByDate[$date] = 0;
            }
            
            $salesByDate[$date] += $sales;
            $expensesByDate[$date] += $expenses;
        }
        
        ksort($salesByDate);
        ksort($expensesByDate);
        
        return [
            'labels' => array_keys($salesByDate),
            'sales' => array_values($salesByDate),
            'expenses' => array_values($expensesByDate),
        ];
    }

    /**
     * Gastos agregados por categoría (query optimizada en BD).
     * Respeta filtros de tienda y período del dashboard.
     */
    private function getExpensesByCategory($selectedStore, $period, $user, $fromDate = null, $toDate = null)
    {
        $query = FinancialEntry::query()
            ->where('type', 'expense')
            ->selectRaw('COALESCE(NULLIF(expense_category, ""), \'otros\') as category, SUM(COALESCE(expense_amount, amount)) as total')
            ->groupByRaw('COALESCE(NULLIF(expense_category, ""), \'otros\')')
            ->orderByDesc('total');

        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($selectedStore !== 'all' && $user->canAccessStore((int) $selectedStore)) {
            $query->where('store_id', $selectedStore);
        } else {
            $allowed = $user->getAllowedStoreIds();
            if (!empty($allowed)) {
                $query->whereIn('store_id', $allowed);
            }
        }

        $this->applyPeriodFilter($query, $period, $fromDate, $toDate);

        return $query->get();
    }

    /**
     * Ingresos agregados por método de pago (query optimizada en BD).
     * Respeta filtros de tienda y período del dashboard.
     */
    private function getIncomeByPaymentMethod($selectedStore, $period, $user, $fromDate = null, $toDate = null)
    {
        $normalizedMethod = "CASE
            WHEN expense_payment_method IN ('card','datafono','tarjeta') THEN 'Tarjeta'
            WHEN expense_payment_method = 'cash' THEN 'Efectivo'
            WHEN expense_payment_method = 'bank' THEN 'Banco'
            WHEN expense_payment_method = 'transfer' THEN 'Transferencia'
            ELSE 'Otros'
        END";

        $query = FinancialEntry::query()
            ->where('type', 'income')
            ->selectRaw("{$normalizedMethod} as method, SUM(COALESCE(income_amount, amount)) as total")
            ->groupByRaw($normalizedMethod)
            ->havingRaw('SUM(COALESCE(income_amount, amount)) > 0')
            ->orderByDesc('total');

        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($selectedStore !== 'all' && $user->canAccessStore((int) $selectedStore)) {
            $query->where('store_id', $selectedStore);
        } else {
            $allowed = $user->getAllowedStoreIds();
            if (!empty($allowed)) {
                $query->whereIn('store_id', $allowed);
            }
        }

        $this->applyPeriodFilter($query, $period, $fromDate, $toDate);

        return $query->get();
    }

    /**
     * Pedidos: total pagado vs pendiente en el período.
     */
    private function getOrdersPaidVsPending($selectedStore, $period, $user, $fromDate = null, $toDate = null)
    {
        $query = Order::query()->with('payments');

        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where('store_id', $enforcedStoreId);
        } elseif ($selectedStore !== 'all' && $user->canAccessStore((int) $selectedStore)) {
            $query->where('store_id', $selectedStore);
        } else {
            $allowed = $user->getAllowedStoreIds();
            if (!empty($allowed)) {
                $query->whereIn('store_id', $allowed);
            }
        }

        $this->applyPeriodFilter($query, $period, $fromDate, $toDate);

        $orders = $query->get();
        $paid = 0;
        $pending = 0;

        foreach ($orders as $order) {
            $orderPaid = $order->payments->sum('amount');
            $paid += $orderPaid;
            $pending += max(0, (float) $order->amount - $orderPaid);
        }

        return ['paid' => $paid, 'pending' => $pending];
    }

    /**
     * Horas extra y festivos por tienda (o por empleado si solo hay una tienda).
     */
    private function getOvertimeByStore($selectedStore, $period, $user, $fromDate = null, $toDate = null)
    {
        $storeIds = $this->resolveStoreIds($selectedStore, $user);
        if (empty($storeIds)) {
            return collect();
        }

        $employeeIds = DB::table('employee_store')
            ->whereIn('store_id', $storeIds)
            ->distinct()
            ->pluck('employee_id');

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        [$start, $end] = $this->getDateRangeForPeriod($period, $fromDate, $toDate);
        $records = OvertimeRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start, $end])
            ->with('employee.stores')
            ->get();

        $byKey = [];
        $singleStore = count($storeIds) === 1;

        foreach ($records as $record) {
            $employee = $record->employee;
            if (!$employee) {
                continue;
            }
            [$priceOvertime, $priceSunday] = OvertimeSetting::getPriceForEmployee($employee->id);
            $hoursOvertime = (float) ($record->overtime_hours ?? 0);
            $hoursSunday = (float) ($record->sunday_holiday_hours ?? 0);
            $amountOvertime = $hoursOvertime * $priceOvertime;
            $amountSunday = $hoursSunday * $priceSunday;

            $stores = $employee->stores;
            foreach ($stores as $store) {
                if (!in_array($store->id, $storeIds)) {
                    continue;
                }
                $key = $singleStore ? 'employee_' . $employee->id : 'store_' . $store->id;
                $label = $singleStore ? $employee->full_name : $store->name;
                if (!isset($byKey[$key])) {
                    $byKey[$key] = [
                        'label' => $label,
                        'store_id' => $store->id,
                        'hours_overtime' => 0,
                        'hours_sunday' => 0,
                        'amount_overtime' => 0,
                        'amount_sunday' => 0,
                    ];
                }
                $byKey[$key]['hours_overtime'] += $hoursOvertime;
                $byKey[$key]['hours_sunday'] += $hoursSunday;
                $byKey[$key]['amount_overtime'] += $amountOvertime;
                $byKey[$key]['amount_sunday'] += $amountSunday;
            }
        }

        return collect(array_values($byKey));
    }

    private function resolveStoreIds($selectedStore, $user): array
    {
        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            return [(int) $enforcedStoreId];
        }
        if ($selectedStore === 'all') {
            $allowed = $user->getAllowedStoreIds();
            return !empty($allowed) ? $allowed : Store::pluck('id')->all();
        }
        if ($user->canAccessStore((int) $selectedStore)) {
            return [(int) $selectedStore];
        }
        return $user->getAllowedStoreIds();
    }

    /**
     * Obtener layout de widgets para el usuario.
     * NUNCA devuelve vacío: si no hay layout guardado o está vacío, devuelve layout por defecto.
     */
    private function getWidgetLayoutForUser($user): array
    {
        $saved = DashboardWidget::forUser($user->id)
            ->orderBy('sort_order')
            ->get();

        if ($saved->isNotEmpty()) {
            $available = WidgetRegistry::getAvailableKeys($user);
            $layout = $saved
                ->filter(fn ($w) => WidgetRegistry::has($w->widget_key) && in_array($w->widget_key, $available, true))
                ->values()
                ->map(fn ($w) => [
                    'key' => $w->widget_key,
                    'x' => $w->pos_x,
                    'y' => $w->pos_y,
                    'w' => $w->width,
                    'h' => $w->height,
                    'minimized' => $w->minimized,
                ])->toArray();

            if (!empty($layout)) {
                return $layout;
            }
        }

        return WidgetRegistry::defaultLayout($user);
    }

    /**
     * API: GET layout de widgets.
     */
    public function getWidgetLayout(Request $request): JsonResponse
    {
        $user = Auth::user();
        $layout = $this->getWidgetLayoutForUser($user);
        return response()->json(['widgets' => $layout]);
    }

    /**
     * API: POST guardar layout de widgets.
     */
    public function storeWidgetLayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.key' => 'required|string|max:50',
            'widgets.*.x' => 'required|integer|min:0',
            'widgets.*.y' => 'required|integer|min:0',
            'widgets.*.w' => 'required|integer|min:1|max:12',
            'widgets.*.h' => 'required|integer|min:1|max:12',
            'widgets.*.minimized' => 'boolean',
        ]);

        $user = Auth::user();
        $available = WidgetRegistry::getAvailableKeys($user);

        foreach ($validated['widgets'] as $i => $item) {
            $key = $item['key'];
            if (!WidgetRegistry::has($key) || !in_array($key, $available, true)) {
                continue;
            }
            DashboardWidget::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'widget_key' => $key,
                ],
                [
                    'pos_x' => $item['x'],
                    'pos_y' => $item['y'],
                    'width' => $item['w'],
                    'height' => $item['h'],
                    'minimized' => $item['minimized'] ?? false,
                    'sort_order' => $i,
                ]
            );
        }

        $userWidgetKeys = array_column($validated['widgets'], 'key');
        DashboardWidget::forUser($user->id)
            ->whereNotIn('widget_key', $userWidgetKeys)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * API: POST restablecer layout por defecto (elimina layout guardado).
     */
    public function resetWidgetLayout(Request $request): JsonResponse
    {
        $user = Auth::user();
        DashboardWidget::forUser($user->id)->delete();
        return response()->json(['success' => true]);
    }
}
