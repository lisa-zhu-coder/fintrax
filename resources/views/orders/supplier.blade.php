@extends('layouts.app')

@section('title', 'Pedidos - ' . $supplier->name)

@section('content')
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
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2 text-right">Pedidos</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Pagado</th>
                        <th class="px-3 py-2 text-right">Pendiente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($summaryByStore as $storeSummary)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-semibold">{{ $storeSummary['store_name'] }}</td>
                            <td class="px-3 py-2 text-right">{{ $storeSummary['total_orders'] }}</td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ number_format($storeSummary['total_amount'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right text-emerald-700 whitespace-nowrap">{{ number_format($storeSummary['total_paid'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $storeSummary['total_pending'] > 0 ? 'text-amber-700' : 'text-emerald-700' }} whitespace-nowrap">
                                {{ number_format($storeSummary['total_pending'], 2, ',', '.') }} €
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Filtros (encima del listado de pedidos) -->
    <div class="mt-10 rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-3 text-base font-semibold">Filtros</h2>
        <form id="supplier-orders-filter-form" method="GET" action="{{ route('orders.supplier', $supplier) }}" class="space-y-4">
            <div class="flex flex-wrap items-end gap-4">
                <label class="block min-w-[200px]">
                    <span class="text-xs font-semibold text-slate-700">Buscar</span>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nº factura o Nº pedido" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block min-w-[140px]">
                    <span class="text-xs font-semibold text-slate-700">Tienda</span>
                    <select name="store_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ ($filters['store_id'] ?? '') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
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
                <label class="block min-w-[160px]">
                    <span class="text-xs font-semibold text-slate-700">Forma de pago</span>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas</option>
                        <option value="cash" {{ ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="transfer" {{ ($filters['payment_method'] ?? '') === 'transfer' ? 'selected' : '' }}>Transferencia</option>
                        <option value="card" {{ ($filters['payment_method'] ?? '') === 'card' ? 'selected' : '' }}>Tarjeta</option>
                    </select>
                </label>
                <label class="block min-w-[140px]">
                    <span class="text-xs font-semibold text-slate-700">Estado</span>
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ ($filters['status'] ?? '') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="pagado" {{ ($filters['status'] ?? '') === 'pagado' ? 'selected' : '' }}>Pagado</option>
                    </select>
                </label>
                <div class="flex items-center gap-2">
                    <button type="submit" form="supplier-orders-filter-form" id="supplier-orders-filter-btn" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Filtrar</button>
                    <a href="{{ route('orders.supplier', $supplier) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Listado de pedidos -->
    <div class="mt-10 rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Pedidos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2">Nº Factura</th>
                        <th class="px-3 py-2">Nº Pedido</th>
                        <th class="px-3 py-2">Concepto</th>
                        <th class="px-3 py-2 text-right">Importe</th>
                        <th class="px-3 py-2 text-right">Pagado</th>
                        <th class="px-3 py-2 text-right">Pendiente</th>
                        <th class="px-3 py-2">Formas de pago</th>
                        <th class="px-3 py-2 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $order->status === 'pagado' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} ring-1">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ $order->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $order->store->name }}</td>
                            <td class="px-3 py-2">{{ $order->invoice_number ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $order->order_number }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                    {{ ucfirst($order->concept) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ number_format($order->amount, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold text-emerald-700 whitespace-nowrap">{{ number_format($order->total_paid, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold {{ ($order->amount - $order->total_paid) > 0 ? 'text-amber-700' : 'text-emerald-700' }} whitespace-nowrap">
                                {{ number_format(max(0, $order->amount - $order->total_paid), 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-slate-600">
                                @php
                                    $methods = $order->payments->pluck('method')->unique()->map(function ($m) {
                                        return match($m) {
                                            'cash' => 'Efectivo',
                                            'transfer' => 'Transferencia',
                                            'card' => 'Tarjeta',
                                            'bank' => 'Transferencia',
                                            default => ucfirst($m ?? '—'),
                                        };
                                    })->unique()->values()->implode(', ');
                                @endphp
                                {{ $methods ?: '—' }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-1">
                                    @php $invoice = $order->invoice(); @endphp
                                    @if($invoice)
                                        @php $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->exists($invoice->file_path) ? \Illuminate\Support\Facades\Storage::disk('local')->mimeType($invoice->file_path) : 'application/octet-stream'; @endphp
                                        <a href="#" data-invoice-preview data-invoice-id="{{ $invoice->id }}"
                                           data-invoice-title="Previsualizar Factura"
                                           data-invoice-subtitle="{{ $invoice->supplier_name ?: 'Sin proveedor' }} - {{ $invoice->invoice_number ?: 'Sin número' }}"
                                           data-invoice-serve="{{ route('invoices.serve', $invoice->id) }}"
                                           data-invoice-download="{{ route('invoices.download', $invoice->id) }}"
                                           data-invoice-mime="{{ $mimeType }}"
                                           class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600" title="Ver factura">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </a>
                                    @else
                                        <span class="inline-flex w-8 justify-center text-slate-300">—</span>
                                    @endif
                                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600" title="Ver">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                    @if(auth()->user()->hasPermission('orders.main.edit'))
                                    <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-brand-50 hover:text-brand-600" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('orders.main.delete'))
                                    <form method="POST" action="{{ route('orders.destroy', $order) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke-linecap="round" stroke-linejoin="round"/><path d="m10 11 6 6m0-6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-6 text-center text-slate-500">No hay pedidos para este proveedor</td>
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
