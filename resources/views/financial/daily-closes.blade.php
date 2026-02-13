@extends('layouts.app')

@section('title', 'Cierres Diarios')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Cierres Diarios</h1>
                <p class="text-sm text-slate-500">Registros de cierres de caja diarios</p>
            </div>
            <div class="flex items-center gap-3">
                @if(auth()->user()->hasPermission('financial.daily_closes.create'))
                <a href="{{ route('financial.create', ['type' => 'daily_close']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir cierre diario
                </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('financial.daily-closes') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda</span>
                    <select name="store" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="all" {{ request('store') === 'all' ? 'selected' : '' }}>Todas las tiendas</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                
                <label class="block" id="periodLabel">
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
                
                <div class="flex items-end" id="filterButtonContainer">
                    <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Filtrar
                    </button>
                </div>
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
        </form>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.daily-closes', array_merge(request()->query(), ['sort_by' => 'date', 'sort_dir' => request('sort_by') === 'date' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
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
                            <a href="{{ route('financial.daily-closes', array_merge(request()->query(), ['sort_by' => 'sales', 'sort_dir' => request('sort_by') === 'sales' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Ventas
                                @if(request('sort_by') === 'sales')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2">TPV</th>
                        <th class="px-3 py-2">Efectivo</th>
                        <th class="px-3 py-2 text-right cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.daily-closes', array_merge(request()->query(), ['sort_by' => 'amount', 'sort_dir' => request('sort_by') === 'amount' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center justify-end gap-1">
                                Total
                                @if(request('sort_by') === 'amount')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $entry->store->name }}</td>
                            <td class="px-3 py-2">
                                @if(isset($entry->sales))
                                    {{ number_format($entry->sales, 2, ',', '.') }} €
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if(isset($entry->tpv))
                                    {{ number_format($entry->tpv, 2, ',', '.') }} €
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if(isset($entry->cash_count))
                                    @php
                                        $cashTotal = 0;
                                        foreach($entry->cash_count as $denomination => $count) {
                                            $cashTotal += (float)$denomination * (int)$count;
                                        }
                                    @endphp
                                    {{ number_format($cashTotal, 2, ',', '.') }} €
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-blue-700">
                                {{ number_format($entry->amount, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('financial.show', $entry->id) }}" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100" title="Ver">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @if(auth()->user()->hasPermission('financial.daily_closes.edit') || auth()->user()->hasPermission('financial.registros.edit'))
                                    <a href="{{ route('financial.edit', $entry->id) }}" class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('financial.daily_closes.delete'))
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
                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">No hay registros</td>
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
