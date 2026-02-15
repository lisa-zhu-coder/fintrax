@extends('layouts.app')

@section('title', 'Registros Financieros')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white dark:bg-slate-800 p-4 shadow-soft ring-1 ring-slate-100 dark:ring-slate-700">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Registros Financieros</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Gestiona ingresos, gastos y cierres diarios</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
                @if(auth()->user()->hasPermission('financial.registros.export'))
                <a href="{{ route('financial.export', request()->query()) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 whitespace-nowrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Exportar CSV
                </a>
                @endif
                @if(auth()->user()->hasPermission('financial.registros.create'))
                <a href="{{ route('financial.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 whitespace-nowrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir registro
                </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('financial.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Búsqueda</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por concepto, notas..." class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                
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
                        <option value="last_7" {{ ($period ?? request('period')) === 'last_7' ? 'selected' : '' }}>Últimos 7 días</option>
                        <option value="last_30" {{ ($period ?? request('period')) === 'last_30' ? 'selected' : '' }}>Últimos 30 días</option>
                        <option value="last_90" {{ ($period ?? request('period')) === 'last_90' ? 'selected' : '' }}>Últimos 90 días</option>
                        <option value="this_month" {{ ($period ?? request('period')) === 'this_month' ? 'selected' : '' }}>Este mes</option>
                        <option value="last_month" {{ ($period ?? request('period')) === 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                        <option value="this_year" {{ ($period ?? request('period')) === 'this_year' ? 'selected' : '' }}>Este año</option>
                        <option value="custom" {{ request('date_from') && request('date_to') ? 'selected' : '' }}>Personalizado</option>
                    </select>
                </label>
                
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo</span>
                    <select name="type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="all" {{ request('type') === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="daily_close" {{ request('type') === 'daily_close' ? 'selected' : '' }}>Cierre diario</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Gasto</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Ingreso</option>
                        <option value="expense_refund" {{ request('type') === 'expense_refund' ? 'selected' : '' }}>Devolución</option>
                    </select>
                </label>
            </div>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Categoría</span>
                    <select name="category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas</option>
                        <optgroup label="Gastos">
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
                        </optgroup>
                        <optgroup label="Ingresos">
                            <option value="servicios_financieros" {{ request('category') === 'servicios_financieros' ? 'selected' : '' }}>Servicios financieros</option>
                            <option value="ventas" {{ request('category') === 'ventas' ? 'selected' : '' }}>Ventas</option>
                        </optgroup>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Usuario</span>
                    <select name="user" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todos</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>
                
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Filtrar
                    </button>
                    <a href="{{ route('financial.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Limpiar
                    </a>
                </div>
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
                            <a href="{{ route('financial.index', array_merge(request()->query(), ['sort_by' => 'date', 'sort_dir' => request('sort_by') === 'date' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
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
                            <a href="{{ route('financial.index', array_merge(request()->query(), ['sort_by' => 'type', 'sort_dir' => request('sort_by') === 'type' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Tipo
                                @if(request('sort_by') === 'type')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2 cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.index', array_merge(request()->query(), ['sort_by' => 'concept', 'sort_dir' => request('sort_by') === 'concept' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1">
                                Concepto
                                @if(request('sort_by') === 'concept')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ request('sort_dir') === 'asc' ? '' : 'rotate-180' }}">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-3 py-2 text-right cursor-pointer hover:bg-slate-50 select-none">
                            <a href="{{ route('financial.index', array_merge(request()->query(), ['sort_by' => 'amount', 'sort_dir' => request('sort_by') === 'amount' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center justify-end gap-1">
                                Importe
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
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium 
                                    {{ $entry->type === 'income' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $entry->type === 'expense' ? 'bg-rose-100 text-rose-700' : '' }}
                                    {{ $entry->type === 'daily_close' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $entry->type === 'expense_refund' ? 'bg-amber-100 text-amber-700' : '' }}
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $entry->type)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ $entry->concept ?? '—' }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $entry->type === 'income' ? 'text-emerald-700' : 'text-rose-700' }}">
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
                                    @php
                                        $canEditEntry = match($entry->type ?? '') {
                                            'daily_close' => auth()->user()->hasPermission('financial.daily_closes.edit'),
                                            'income' => auth()->user()->hasPermission('financial.income.edit'),
                                            'expense' => auth()->user()->hasPermission('financial.expenses.edit'),
                                            default => auth()->user()->hasPermission('financial.registros.edit'),
                                        };
                                    @endphp
                                    @if($canEditEntry)
                                    <a href="{{ route('financial.edit', $entry->id) }}" class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('financial.registros.delete'))
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
                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">No hay registros</td>
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
