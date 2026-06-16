@extends('layouts.app')

@section('title', 'Pedidos - ' . $supplier->name)

@section('content')
@php
    use App\Support\OrderTableSettings;

    $supplierOrdersSortUrl = function (string $column, string $defaultDir = 'asc') use ($supplier) {
        return route('orders.supplier', array_merge(['supplier' => $supplier], request()->query(), [
            'sort_by' => $column,
            'sort_dir' => request('sort_by') === $column
                ? (request('sort_dir') === 'desc' ? 'asc' : 'desc')
                : $defaultDir,
        ]));
    };
    $storeSummarySortUrl = function (string $column, string $defaultDir = 'asc') use ($supplier) {
        return route('orders.supplier', array_merge(['supplier' => $supplier], request()->query(), [
            'store_sort_by' => $column,
            'store_sort_dir' => request('store_sort_by') === $column
                ? (request('store_sort_dir') === 'desc' ? 'asc' : 'desc')
                : $defaultDir,
        ]));
    };
    $supplierStoreSummaryColumns = OrderTableSettings::visibleColumns('supplier_store_summary');
    $supplierOrdersColumns = OrderTableSettings::visibleColumns('supplier_orders');
    $supplierOrdersFilters = $allowedFilters ?? OrderTableSettings::supplierOrdersFilterKeys();
@endphp
<div class="flex flex-col">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('orders.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-1 inline-block">← Pedidos</a>
                <h1 class="text-lg font-semibold">{{ $supplier->name }}</h1>
                <p class="text-sm text-slate-500">
                    @if($supplier->expenseCategory)
                        {{ $supplier->expenseCategory->name }}
                    @elseif($supplier->type)
                        {{ \App\Models\Supplier::TYPES[$supplier->type] ?? ucfirst(str_replace('_', ' ', $supplier->type)) }}
                    @else
                        Pedidos de este proveedor
                    @endif
                </p>
            </div>
            @if(auth()->user()->hasPermission('orders.main.create'))
            <a href="{{ route('orders.create') }}?supplier_id={{ $supplier->id }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Añadir pedido
                </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Resumen por tienda (primero, según especificación) -->
    @if(!empty($summaryByStore))
    <div class="mt-10 rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Resumen por tienda</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        @foreach($supplierStoreSummaryColumns as $column)
                            @include('partials.orders.table-header', [
                                'column' => $column,
                                'sortUrlCallback' => $storeSummarySortUrl,
                                'sortByKey' => 'store_sort_by',
                                'sortDirKey' => 'store_sort_dir',
                                'defaultDir' => str_contains($column['sort_key'] ?? '', 'store_name') ? 'asc' : 'desc',
                            ])
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($summaryByStore as $storeSummary)
                        <tr class="hover:bg-slate-50">
                            @foreach($supplierStoreSummaryColumns as $column)
                                @include('partials.orders.supplier-store-summary-cell', ['column' => $column, 'storeSummary' => $storeSummary])
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Filtros (encima del listado de pedidos) -->
    @if(!empty($supplierOrdersFilters))
    <div class="mt-10 rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-3 text-base font-semibold">Filtros</h2>
        <form id="supplier-orders-filter-form" method="GET" action="{{ route('orders.supplier', $supplier) }}" class="space-y-4">
            @if(request('sort_by'))
                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
            @endif
            @if(request('sort_dir'))
                <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
            @endif
            @if(request('store_sort_by'))
                <input type="hidden" name="store_sort_by" value="{{ request('store_sort_by') }}">
            @endif
            @if(request('store_sort_dir'))
                <input type="hidden" name="store_sort_dir" value="{{ request('store_sort_dir') }}">
            @endif
            <div class="flex flex-wrap items-end gap-4">
                @if(in_array('search', $supplierOrdersFilters, true))
                <label class="block min-w-[200px]">
                    <span class="text-xs font-semibold text-slate-700">Buscar</span>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nº factura o Nº pedido" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                @endif
                @if(in_array('store_id', $supplierOrdersFilters, true))
                <div class="min-w-[140px]">@include('partials.store-filter-select', ['name' => 'store_id', 'stores' => $stores, 'selected' => $filters['store_id'] ?? '', 'label' => OrderTableSettings::supplierOrdersColumnLabel('store'), 'showAllOption' => true])</div>
                @endif
                @if(in_array('split_type', $supplierOrdersFilters, true))
                <label class="block min-w-[140px]">
                    <span class="text-xs font-semibold text-slate-700">{{ OrderTableSettings::supplierOrdersColumnLabel('split_type') }}</span>
                    <select name="split_type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todos</option>
                        <option value="conjunto" {{ ($filters['split_type'] ?? '') === 'conjunto' ? 'selected' : '' }}>Conjunto</option>
                        <option value="individual" {{ ($filters['split_type'] ?? '') === 'individual' ? 'selected' : '' }}>Individual</option>
                    </select>
                </label>
                @endif
                @if(in_array('origin_store_id', $supplierOrdersFilters, true))
                <div class="min-w-[160px]">@include('partials.store-filter-select', ['name' => 'origin_store_id', 'stores' => $stores, 'selected' => $filters['origin_store_id'] ?? '', 'label' => OrderTableSettings::supplierOrdersColumnLabel('origin_store'), 'showAllOption' => true])</div>
                @endif
                @if(in_array('period', $supplierOrdersFilters, true))
                <label class="block min-w-[180px]">
                    <span class="text-xs font-semibold text-slate-700">Período</span>
                    <select name="period" id="periodSelect" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="last_7" {{ ($filters['period'] ?? 'this_year') === 'last_7' ? 'selected' : '' }}>Últimos 7 días</option>
                        <option value="last_30" {{ ($filters['period'] ?? 'this_year') === 'last_30' ? 'selected' : '' }}>Últimos 30 días</option>
                        <option value="last_90" {{ ($filters['period'] ?? '') === 'last_90' ? 'selected' : '' }}>Últimos 90 días</option>
                        <option value="this_month" {{ ($filters['period'] ?? '') === 'this_month' ? 'selected' : '' }}>Este mes</option>
                        <option value="last_month" {{ ($filters['period'] ?? '') === 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                        <option value="this_year" {{ ($filters['period'] ?? 'this_year') === 'this_year' ? 'selected' : '' }}>Este año</option>
                        <option value="custom" {{ ($filters['period'] ?? '') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </label>
                <div id="customDateRange" class="{{ ($filters['period'] ?? '') === 'custom' ? 'grid grid-cols-2 gap-2' : 'hidden' }}">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Desde</span>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Hasta</span>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                </div>
                @endif
                @if(in_array('concept', $supplierOrdersFilters, true))
                <label class="block min-w-[160px]">
                    <span class="text-xs font-semibold text-slate-700">{{ OrderTableSettings::supplierOrdersColumnLabel('concept') }}</span>
                    <select name="concept" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todos</option>
                        <option value="pedido" {{ ($filters['concept'] ?? '') === 'pedido' ? 'selected' : '' }}>Pedido</option>
                        <option value="royalty" {{ ($filters['concept'] ?? '') === 'royalty' ? 'selected' : '' }}>Royalty</option>
                        <option value="rectificacion" {{ ($filters['concept'] ?? '') === 'rectificacion' ? 'selected' : '' }}>Rectificación</option>
                        <option value="tara" {{ ($filters['concept'] ?? '') === 'tara' ? 'selected' : '' }}>Tara</option>
                    </select>
                </label>
                @endif
                @if(in_array('payment_method', $supplierOrdersFilters, true))
                <label class="block min-w-[160px]">
                    <span class="text-xs font-semibold text-slate-700">{{ OrderTableSettings::supplierOrdersColumnLabel('payment_methods') }}</span>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas</option>
                        <option value="cash" {{ ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="transfer" {{ ($filters['payment_method'] ?? '') === 'transfer' ? 'selected' : '' }}>Transferencia</option>
                        <option value="card" {{ ($filters['payment_method'] ?? '') === 'card' ? 'selected' : '' }}>Tarjeta</option>
                    </select>
                </label>
                @endif
                @if(in_array('status', $supplierOrdersFilters, true))
                <label class="block min-w-[140px]">
                    <span class="text-xs font-semibold text-slate-700">{{ OrderTableSettings::supplierOrdersColumnLabel('status') }}</span>
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ ($filters['status'] ?? '') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="pagado" {{ ($filters['status'] ?? '') === 'pagado' ? 'selected' : '' }}>Pagado</option>
                    </select>
                </label>
                @endif
                <div class="flex items-center gap-2">
                    <button type="submit" form="supplier-orders-filter-form" id="supplier-orders-filter-btn" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Filtrar</button>
                    <a href="{{ route('orders.supplier', $supplier) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
    @endif

    <!-- Listado de pedidos -->
    <div class="mt-10 rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Pedidos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        @foreach($supplierOrdersColumns as $column)
                            @include('partials.orders.table-header', [
                                'column' => $column,
                                'sortUrlCallback' => $supplierOrdersSortUrl,
                                'defaultDir' => match($column['sort_key'] ?? '') {
                                    'date', 'amount', 'total_paid', 'pending' => 'desc',
                                    default => 'asc',
                                },
                            ])
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50">
                            @foreach($supplierOrdersColumns as $column)
                                @include('partials.orders.supplier-orders-cell', [
                                    'column' => $column,
                                    'order' => $order,
                                    'originStoresById' => $originStoresById,
                                ])
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($supplierOrdersColumns) }}" class="px-3 py-6 text-center text-slate-500">No hay pedidos para este proveedor</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var periodSelect = document.getElementById('periodSelect');
    var customDateRange = document.getElementById('customDateRange');
    if (periodSelect && customDateRange) {
        function toggleCustomDates() {
            if (periodSelect.value === 'custom') {
                customDateRange.classList.remove('hidden');
                customDateRange.classList.add('grid', 'grid-cols-2', 'gap-2');
            } else {
                customDateRange.classList.add('hidden');
                customDateRange.classList.remove('grid', 'grid-cols-2', 'gap-2');
            }
        }
        toggleCustomDates();
        periodSelect.addEventListener('change', toggleCustomDates);
    }

    // Asegurar que el botón Filtrar envíe el formulario (por si en producción el submit nativo falla)
    var filterForm = document.getElementById('supplier-orders-filter-form');
    var filterBtn = document.getElementById('supplier-orders-filter-btn');
    if (filterForm && filterBtn) {
        filterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            filterForm.submit();
        });
    }
});
</script>
@endsection
