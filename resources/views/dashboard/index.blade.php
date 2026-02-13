@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
.widget-content canvas {
    max-height: 200px;
}
</style>
@endpush

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white dark:bg-slate-800 p-4 shadow-soft ring-1 ring-slate-100 dark:ring-slate-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Dashboard</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Resumen financiero de las tiendas</p>
            </div>
            <button type="button" id="dashboardResetLayoutBtn" class="inline-flex items-center gap-2 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 px-4 py-2 text-sm font-semibold text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50" title="Restaurar layout por defecto">
                Restaurar layout
            </button>
        </div>
    </header>

    @php
        $canCashWithdrawal = auth()->user()->hasPermission('treasury.cash_control.view');
        $canCashDeposit = auth()->user()->hasPermission('treasury.cash_wallets.create');
        $defaultStoreId = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() ? '' : (auth()->user()->store_id ?? '');
    @endphp

    <!-- Accesos rápidos (siempre primero) -->
    @if($canCashWithdrawal || $canCashDeposit || auth()->user()->hasPermission('financial.daily_closes.create') || auth()->user()->hasPermission('orders.main.create'))
    <div class="rounded-2xl bg-white dark:bg-slate-800 p-4 shadow-soft ring-1 ring-slate-100 dark:ring-slate-700">
        <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-slate-100">Accesos rápidos</h2>
        @include('dashboard.widgets.quick_actions')
    </div>
    @endif

    <!-- Modales -->
    @if($canCashWithdrawal)
    <div id="modalRetirarEfectivo" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" onclick="this.closest('.fixed').querySelector('#modalRetirarEfectivo').classList.add('hidden')"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold">Retirar efectivo</h3>
                <p class="mb-4 text-sm text-slate-500">Tienda → Cartera</p>
                <form method="POST" action="{{ route('financial.cash-withdrawals.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="redirect_to" value="dashboard">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Tienda origen *</span>
                        <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ $defaultStoreId == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Cartera destino *</span>
                        <select name="cash_wallet_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @foreach($cashWallets as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                        <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="0,00">
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('modalRetirarEfectivo').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($canCashDeposit)
    <div id="modalIngresarDinero" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" onclick="document.getElementById('modalIngresarDinero').classList.add('hidden')"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold">Ingresar dinero</h3>
                <p class="mb-4 text-sm text-slate-500">Cartera → Tienda</p>
                <form method="POST" action="{{ route('financial.cash-deposits.store') }}" class="space-y-4">
                    @csrf
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Cartera origen *</span>
                        <select name="cash_wallet_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @foreach($cashWallets as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Tienda destino *</span>
                        <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ $defaultStoreId == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                        <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="0,00">
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('modalIngresarDinero').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Tienda</span>
                <select name="store" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="all" {{ $selectedStore === 'all' ? 'selected' : '' }}>Todas las tiendas</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ $selectedStore == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Período</span>
                <select name="period" id="periodSelect" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Este mes</option>
                    <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                    <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>Este año</option>
                    <option value="last_year" {{ $period === 'last_year' ? 'selected' : '' }}>Año pasado</option>
                    <option value="last_7" {{ $period === 'last_7' ? 'selected' : '' }}>Últimos 7 días</option>
                    <option value="last_30" {{ $period === 'last_30' ? 'selected' : '' }}>Últimos 30 días</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Fecha personalizada</option>
                </select>
            </label>

            <div id="customDateRange" class="{{ $period === 'custom' ? 'grid' : 'hidden' }} grid-cols-2 gap-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Desde</span>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Hasta</span>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                </label>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Resumen -->
    @php
        $canViewIncome = auth()->user()->hasPermission('dashboard.income.view');
        $canViewExpenses = auth()->user()->hasPermission('dashboard.expenses.view');
        $canViewProfit = auth()->user()->hasPermission('dashboard.profit.view');
        $canViewChart = auth()->user()->hasPermission('dashboard.chart.view');
        $canViewRecords = auth()->user()->hasPermission('dashboard.records.view');
        $summaryCardsCount = ($canViewIncome ? 1 : 0) + ($canViewExpenses ? 1 : 0) + ($canViewProfit ? 1 : 0);
    @endphp

    @if($summaryCardsCount > 0)
    <div class="grid grid-cols-1 gap-4 md:grid-cols-{{ $summaryCardsCount }}">
        @if($canViewIncome)
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Ingresos</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($summary['total_income'], 2, ',', '.') }} €</div>
        </div>
        @endif
        
        @if($canViewExpenses)
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Gastos</div>
            <div class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format($summary['total_expenses'], 2, ',', '.') }} €</div>
        </div>
        @endif
        
        @if($canViewProfit)
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Beneficio</div>
            <div class="mt-1 text-2xl font-semibold {{ $summary['net'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}" title="Ingresos - Gastos">
                {{ number_format($summary['net'], 2, ',', '.') }} €
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Evolución ventas y gastos -->
    @if($canViewChart || auth()->user()->hasPermission('dashboard.main.view'))
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Evolución de ventas y gastos</h2>
        @include('dashboard.widgets.sales')
    </div>
    @endif

    <!-- Ingresos y Gastos (2 columnas) -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @if($canViewIncome || auth()->user()->hasPermission('dashboard.main.view'))
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="mb-4 text-base font-semibold">Ingresos por método de pago</h2>
            @include('dashboard.widgets.income')
        </div>
        @endif
        @if($canViewExpenses || auth()->user()->hasPermission('dashboard.main.view'))
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="mb-4 text-base font-semibold">Gastos por categoría</h2>
            @include('dashboard.widgets.expenses')
        </div>
        @endif
    </div>

    <!-- Widgets adicionales (pedidos, horas extras) desde layout -->
    @php
        $extraWidgetKeys = ['pedidos_pagado_vs_pendiente', 'horas_extras_rrhh'];
        $extraLayout = array_filter($widgetLayout ?? [], fn($w) => in_array($w['key'], $extraWidgetKeys, true));
    @endphp
    @if(count($extraLayout) > 0)
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach($extraLayout as $widget)
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="mb-4 text-base font-semibold">{{ \App\Support\WidgetRegistry::getLabel($widget['key']) }}</h2>
            @include(\App\Support\WidgetRegistry::getView($widget['key']))
        </div>
        @endforeach
    </div>
    @endif

    <!-- Últimos registros (al final) -->
    @if($canViewRecords || auth()->user()->hasPermission('dashboard.main.view'))
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Últimos registros</h2>
        @include('dashboard.widgets.records')
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodSelect = document.getElementById('periodSelect');
    const customDateRange = document.getElementById('customDateRange');

    if (periodSelect && customDateRange) {
        periodSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.classList.remove('hidden');
                customDateRange.classList.add('grid');
            } else {
                customDateRange.classList.add('hidden');
                customDateRange.classList.remove('grid');
            }
        });
    }

    const resetLayoutBtn = document.getElementById('dashboardResetLayoutBtn');
    if (resetLayoutBtn) {
        resetLayoutBtn.addEventListener('click', async function() {
            if (!confirm('¿Restaurar el layout por defecto? Se perderá tu configuración actual.')) return;
            try {
                const res = await fetch('{{ route("dashboard.widgets.reset") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Error al restablecer');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        });
    }
});
</script>
@if((auth()->user()->hasPermission('dashboard.expenses.view') || auth()->user()->hasPermission('dashboard.main.view')) && $expensesByCategory->isNotEmpty())
@php
    $expenseLabels = $expensesByCategory->map(fn ($r) => ucfirst(str_replace('_', ' ', $r->category)))->values()->toArray();
    $expenseTotals = $expensesByCategory->map(fn ($r) => (float) $r->total)->values()->toArray();
    $expenseCategories = $expensesByCategory->pluck('category')->values()->toArray();
    $expensesUrlParams = array_filter([
        'period' => $period,
        'store' => $selectedStore,
        'date_from' => $fromDate,
        'date_to' => $toDate,
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<script>
(function() {
    const ctx = document.querySelector('[data-chart="expenses"]');
    if (!ctx) return;
    const labels = @json($expenseLabels);
    const data = @json($expenseTotals);
    const categories = @json($expenseCategories);
    const urlParams = @json($expensesUrlParams);
    const baseUrl = '{{ route("financial.expenses") }}';
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Importe (€)',
                data: data,
                backgroundColor: 'rgba(239, 68, 68, 0.6)',
                borderColor: 'rgb(239, 68, 68)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR'
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                }
            },
            onClick: function(e, elements) {
                if (elements.length > 0) {
                    const idx = elements[0].index;
                    const params = new URLSearchParams(urlParams);
                    params.set('category', categories[idx]);
                    window.location.href = baseUrl + '?' + params.toString();
                }
            }
        }
    });
})();
</script>
@endif
@if((auth()->user()->hasPermission('dashboard.income.view') || auth()->user()->hasPermission('dashboard.main.view')) && $incomeByPaymentMethod->isNotEmpty())
@php
    $incomeLabels = $incomeByPaymentMethod->map(fn ($r) => $r->method)->values()->toArray();
    $incomeTotals = $incomeByPaymentMethod->map(fn ($r) => (float) $r->total)->values()->toArray();
@endphp
<script>
(function() {
    const ctx = document.querySelector('[data-chart="income"]');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($incomeLabels),
            datasets: [{
                data: @json($incomeTotals),
                backgroundColor: ['#25AD9F', '#224D5F', '#45beb2', '#b0e4de', '#7dd4ca'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR'
                            }).format(context.parsed.y ?? context.parsed) + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endif
@if(auth()->user()->hasPermission('orders.main.view') && (($ordersPaidVsPending['paid'] ?? 0) + ($ordersPaidVsPending['pending'] ?? 0)) > 0)
@php
    $ordersPaid = $ordersPaidVsPending['paid'] ?? 0;
    $ordersPending = $ordersPaidVsPending['pending'] ?? 0;
@endphp
<script>
(function() {
    const ctx = document.querySelector('[data-chart="orders"]');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pagado', 'Pendiente'],
            datasets: [{
                data: [@json($ordersPaid), @json($ordersPending)],
                backgroundColor: ['#25AD9F', '#224D5F'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR'
                            }).format(context.parsed) + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endif
@if(auth()->user()->hasPermission('hr.overtime.view') && $overtimeByStore->isNotEmpty())
@php
    $overtimeLabels = $overtimeByStore->pluck('label')->values()->toArray();
    $overtimeHours = $overtimeByStore->map(fn ($r) => (float) $r['hours_overtime'])->values()->toArray();
    $overtimeSundayHours = $overtimeByStore->map(fn ($r) => (float) $r['hours_sunday'])->values()->toArray();
    $overtimeRowData = $overtimeByStore->map(fn ($r) => [
        'amount_overtime' => (float) $r['amount_overtime'],
        'amount_sunday' => (float) $r['amount_sunday'],
        'store_id' => $r['store_id'] ?? null,
    ])->values()->toArray();
@endphp
<script>
(function() {
    const ctx = document.querySelector('[data-chart="overtime"]');
    if (!ctx) return;
    const rowData = @json($overtimeRowData);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($overtimeLabels),
            datasets: [
                {
                    label: 'Horas extra',
                    data: @json($overtimeHours),
                    backgroundColor: 'rgba(37, 173, 159, 0.6)',
                    borderColor: 'rgb(37, 173, 159)',
                    borderWidth: 1
                },
                {
                    label: 'Horas domingo/festivo',
                    data: @json($overtimeSundayHours),
                    backgroundColor: 'rgba(34, 77, 95, 0.6)',
                    borderColor: 'rgb(34, 77, 95)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        afterBody: function(context) {
                            if (context.length === 0) return '';
                            const idx = context[0].dataIndex;
                            const row = rowData[idx];
                            if (!row) return '';
                            const fmt = (v) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(v);
                            return 'Importe extra: ' + fmt(row.amount_overtime) + '\nImporte festivo: ' + fmt(row.amount_sunday);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' h';
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endif
@if((auth()->user()->hasPermission('dashboard.chart.view') || auth()->user()->hasPermission('dashboard.main.view')) && count($chartData['labels']) > 0)
<script>
const ctx = document.querySelector('[data-chart="sales"]');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartData['labels']),
            datasets: [
                {
                    label: 'Ventas',
                    data: @json($chartData['sales']),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1,
                    fill: true
                },
                {
                    label: 'Gastos',
                    data: @json($chartData['expenses']),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.1,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR'
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                }
            }
        }
    });
}
</script>
@endif
@endpush
@endsection
