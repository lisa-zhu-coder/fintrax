@extends('layouts.app')

@section('title', 'Control de Banco')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Control de Banco</h1>
                <p class="text-sm text-slate-500">Ingresos y gastos bancarios por tienda</p>
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('financial.bank-control') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda</span>
                    <select name="store_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Todas las tiendas</option>
                        @if(isset($allStores))
                            @foreach($allStores as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Año</span>
                    <select name="year" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @if(isset($availableYears) && is_array($availableYears))
                            @foreach($availableYears as $yearOption)
                                <option value="{{ $yearOption }}" {{ (request('year', $year ?? date('Y')) == $yearOption) ? 'selected' : '' }}>{{ $yearOption }}</option>
                            @endforeach
                        @endif
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Período</span>
                    <select name="period" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="all" {{ request('period', 'all') == 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="last_30" {{ request('period') == 'last_30' ? 'selected' : '' }}>Últimos 30 días</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Mes actual</option>
                        <option value="this_year" {{ request('period') == 'this_year' ? 'selected' : '' }}>Año actual</option>
                    </select>
                </label>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Lista de tiendas con saldos bancarios -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @if(empty($storesData))
            <div class="py-8 text-center text-slate-500">
                No hay registros bancarios
            </div>
        @else
            <div class="space-y-6">
                @foreach($storesData as $store)
                    <div class="border-b border-slate-200 pb-6 last:border-b-0 last:pb-0">
                        <!-- Resumen de tienda -->
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-slate-900 mb-3">{{ $store['name'] }}</h3>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500 mb-1">Ingresos Totales</div>
                                    <div class="text-lg font-semibold text-emerald-700">
                                        {{ number_format($store['total_income'], 2, ',', '.') }} €
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500 mb-1">Gastos Totales</div>
                                    <div class="text-lg font-semibold text-rose-700">
                                        {{ number_format($store['total_expenses'], 2, ',', '.') }} €
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <div class="text-xs text-slate-500 mb-1">Saldo Bancario</div>
                                    <div class="text-lg font-semibold {{ $store['total_balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($store['total_balance'], 2, ',', '.') }} €
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose por meses -->
                        @if(!empty($store['months']))
                            <div class="mt-4">
                                <button type="button" onclick="toggleMonths({{ $store['id'] }})" class="flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-brand-600 mb-3">
                                    <svg id="icon-{{ $store['id'] }}" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="transition-transform">
                                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Ver desglose por meses
                                </button>
                                
                                <div id="months-{{ $store['id'] }}" class="hidden overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Mes</th>
                                                <th class="px-3 py-2 text-right">Ingresos</th>
                                                <th class="px-3 py-2 text-right">Gastos</th>
                                                <th class="px-3 py-2 text-right">Traspasos</th>
                                                <th class="px-3 py-2 text-right">Saldo</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($store['months'] as $month)
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-2 font-medium text-slate-900">
                                                        {{ $month['label'] }}
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-semibold text-emerald-700">
                                                        {{ number_format($month['income'], 2, ',', '.') }} €
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-semibold text-rose-700">
                                                        {{ number_format($month['expenses'], 2, ',', '.') }} €
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-semibold {{ ($month['transfers'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                        {{ number_format($month['transfers'] ?? 0, 2, ',', '.') }} €
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-semibold {{ $month['balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                        {{ number_format($month['balance'], 2, ',', '.') }} €
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
function toggleMonths(storeId) {
    const monthsDiv = document.getElementById('months-' + storeId);
    const icon = document.getElementById('icon-' + storeId);
    
    if (monthsDiv.classList.contains('hidden')) {
        monthsDiv.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        monthsDiv.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>
@endsection
