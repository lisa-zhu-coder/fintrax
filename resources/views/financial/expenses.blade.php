@extends('layouts.app')

@section('title', 'Gastos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Gastos</h1>
                <p class="text-sm text-slate-500">Registros de gastos</p>
            </div>
            <div class="flex items-center gap-3">
                @if(auth()->user()->hasPermission('financial.expenses.create'))
                <a href="{{ route('financial.create', ['type' => 'expense']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir gasto
                </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('financial.expenses') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda</span>
                    <select name="store" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="all" {{ request('store') === 'all' ? 'selected' : '' }}>Todas las tiendas</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Período</span>
                    <select name="period" id="periodSelect" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="last_7" {{ $period === 'last_7' ? 'selected' : '' }}>Últimos 7 días</option>
                        <option value="last_30" {{ $period === 'last_30' ? 'selected' : '' }}>Últimos 30 días</option>
                        <option value="last_90" {{ $period === 'last_90' ? 'selected' : '' }}>Últimos 90 días</option>
                        <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Este mes</option>
                        <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                        <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>Este año</option>
                        <option value="custom" {{ request('date_from') && request('date_to') ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Categoría</span>
                    <select name="category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas</option>
                        <option value="alquiler" {{ request('category') === 'alquiler' ? 'selected' : '' }}>Alquiler</option>
                        <option value="impuestos" {{ request('category') === 'impuestos' ? 'selected' : '' }}>Impuestos</option>
                        <option value="seguridad_social" {{ request('category') === 'seguridad_social' ? 'selected' : '' }}>Seguridad Social</option>
                        <option value="suministros" {{ request('category') === 'suministros' ? 'selected' : '' }}>Suministros</option>
                        <option value="servicios_profesionales" {{ request('category') === 'servicios_profesionales' ? 'selected' : '' }}>Servicios profesionales</option>
                        <option value="sueldos" {{ request('category') === 'sueldos' ? 'selected' : '' }}>Sueldos</option>
                        <option value="miramira" {{ request('category') === 'miramira' ? 'selected' : '' }}>Miramira</option>
                        <option value="mercaderia" {{ request('category') === 'mercaderia' ? 'selected' : '' }}>Mercadería</option>
                        <option value="equipamiento" {{ request('category') === 'equipamiento' ? 'selected' : '' }}>Equipamiento</option>
                        <option value="otros" {{ request('category') === 'otros' ? 'selected' : '' }}>Otros</option>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Modo de pago</span>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todos</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="bank" {{ request('payment_method') === 'bank' ? 'selected' : '' }}>Banco</option>
                    </select>
                </label>
            </div>
            
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="only_pending" value="1" {{ request('only_pending') == '1' ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"/>
                    <span class="text-sm font-semibold text-slate-700">Solo pendientes</span>
                </label>
            </div>
            
            <div id="customDateRange" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Desde</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Hasta</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Filtrar
                </button>
                <a href="{{ route('financial.expenses') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.expenses', array_merge(request()->query(), ['sort_by' => 'date', 'sort_dir' => request('sort_by') === 'date' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Fecha
                                @if(request('sort_by') === 'date')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.expenses', array_merge(request()->query(), ['sort_by' => 'expense_concept', 'sort_dir' => request('sort_by') === 'expense_concept' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Concepto
                                @if(request('sort_by') === 'expense_concept')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.expenses', array_merge(request()->query(), ['sort_by' => 'expense_category', 'sort_dir' => request('sort_by') === 'expense_category' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Categoría
                                @if(request('sort_by') === 'expense_category')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.expenses', array_merge(request()->query(), ['sort_by' => 'expense_payment_method', 'sort_dir' => request('sort_by') === 'expense_payment_method' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Modo de pago
                                @if(request('sort_by') === 'expense_payment_method')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2">Procedencia</th>
                        <th class="px-3 py-2 text-right cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.expenses', array_merge(request()->query(), ['sort_by' => 'amount', 'sort_dir' => request('sort_by') === 'amount' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center justify-end gap-1">
                                Importe
                                @if(request('sort_by') === 'amount')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Pagado</th>
                        <th class="px-3 py-2 text-right">Pendiente</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2">Factura</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $entry->store->name }}</td>
                            <td class="px-3 py-2">{{ $entry->expense_concept ?? $entry->concept ?? '—' }}</td>
                            <td class="px-3 py-2">
                                @if($entry->expense_category)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ ucfirst(str_replace('_', ' ', $entry->expense_category)) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $paymentMethod = $entry->expense_payment_method ?? null;
                                    $paymentLabel = '—';
                                    if ($paymentMethod === 'cash') {
                                        $paymentLabel = 'Efectivo';
                                    } elseif ($paymentMethod === 'bank') {
                                        $paymentLabel = 'Banco';
                                    }
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $paymentMethod === 'cash' ? 'bg-emerald-100 text-emerald-700' : ($paymentMethod === 'bank' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $paymentLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $source = $entry->expense_source ?? null;
                                    $procedenciaLabels = [
                                        'cierre_diario' => 'Cierre diario',
                                        'control_efectivo' => 'Control efectivo',
                                        'pedido' => 'Pedidos',
                                        'cartera' => 'Cartera',
                                        'gasto_manual' => 'Gasto manual',
                                        'conciliacion_bancaria' => 'Conciliación bancaria',
                                        'factura' => 'Factura',
                                    ];
                                    $procedencia = $procedenciaLabels[$source] ?? '—';
                                    if ($procedencia === '—' && $entry->notes) {
                                        $notesDecoded = @json_decode($entry->notes, true);
                                        if (is_array($notesDecoded) && isset($notesDecoded['source'])) {
                                            if ($notesDecoded['source'] === 'cash_control') {
                                                $procedencia = 'Control efectivo';
                                            } elseif (isset($notesDecoded['order_id'])) {
                                                $procedencia = 'Pedidos';
                                            } elseif (isset($notesDecoded['bank_movement_id'])) {
                                                $procedencia = 'Conciliación bancaria';
                                            } elseif (isset($notesDecoded['cash_wallet_id'])) {
                                                $procedencia = 'Cartera';
                                            }
                                        }
                                    }
                                    $procedenciaClass = match($source) {
                                        'control_efectivo' => 'bg-brand-100 text-brand-700',
                                        'pedido' => 'bg-amber-100 text-amber-700',
                                        'cierre_diario' => 'bg-violet-100 text-violet-700',
                                        'cartera' => 'bg-emerald-100 text-emerald-700',
                                        'conciliacion_bancaria' => 'bg-blue-100 text-blue-700',
                                        'factura' => 'bg-slate-100 text-slate-700',
                                        'gasto_manual' => 'bg-slate-100 text-slate-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $procedenciaClass }}">
                                    {{ $procedencia }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-rose-700 whitespace-nowrap">
                                {{ number_format($entry->amount, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-700 whitespace-nowrap">
                                {{ number_format($entry->total_amount ?? $entry->amount, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-600 whitespace-nowrap">
                                {{ number_format($entry->total_paid ?? 0, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right font-semibold {{ $entry->pending_amount > 0 ? 'text-amber-700' : 'text-slate-400' }} whitespace-nowrap">
                                @if($entry->pending_amount > 0)
                                    {{ number_format($entry->pending_amount, 2, ',', '.') }} €
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $status = $entry->status ?? ($entry->total_paid >= ($entry->total_amount ?? $entry->amount) ? 'pagado' : 'pendiente');
                                    $statusLabel = $status === 'pagado' ? 'Pagado' : 'Pendiente';
                                    $statusColor = $status === 'pagado' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                @if($entry->invoice_id && $entry->invoice)
                                    <a href="{{ route('invoices.show', $entry->invoice->id) }}" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700" title="Ver factura">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Ver factura
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('financial.show', $entry->id) }}" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100" title="Ver">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @if(auth()->user()->hasPermission('financial.expenses.edit'))
                                    <a href="{{ route('financial.edit', $entry->id) }}" class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('financial.expenses.delete'))
                                    <form method="POST" action="{{ route('financial.destroy', $entry->id) }}" class="inline" onsubmit="return confirm('¿Estás seguro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" title="Eliminar">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-3 py-6 text-center text-slate-500">No hay registros</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            @if(method_exists($entries, 'links'))
                {{ $entries->links() }}
            @endif
        </div>
    </div>
</div>

<script>
    // Mostrar/ocultar campos de fecha personalizada
    document.addEventListener('DOMContentLoaded', function() {
        const periodSelect = document.getElementById('periodSelect');
        const customDateRange = document.getElementById('customDateRange');
        
        if (periodSelect && customDateRange) {
            function toggleCustomDates() {
                if (periodSelect.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            }
            
            // Verificar estado inicial
            toggleCustomDates();
            
            // Escuchar cambios
            periodSelect.addEventListener('change', toggleCustomDates);
        }
    });
</script>
@endsection
