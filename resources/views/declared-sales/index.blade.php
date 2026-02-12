@extends('layouts.app')

@section('title', 'Ventas Declaradas')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Ventas Declaradas</h1>
                <p class="text-sm text-slate-500">Gestiona las ventas declaradas generadas desde cierres diarios</p>
            </div>
            @if(auth()->user()->hasPermission('declared_sales.main.create'))
            <form method="POST" action="{{ route('declared-sales.generate-from-daily-closes') }}" class="inline" onsubmit="return confirm('¿Generar ventas declaradas desde los cierres diarios del mes seleccionado?')">
                @csrf
                <input type="hidden" name="month" value="{{ request('month', now()->format('Y-m')) }}">
                @if(request('store') && request('store') !== 'all')
                    <input type="hidden" name="store_id" value="{{ request('store') }}">
                @endif
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Generar desde cierres diarios
                </button>
            </form>
            @endif
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('declared-sales.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda</span>
                    <select name="store" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="all" {{ request('store') === 'all' || !request('store') ? 'selected' : '' }}>Todas las tiendas</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Mes</span>
                    <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            
            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                        <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Filtrar
                </button>
                <a href="{{ route('declared-sales.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Limpiar filtros
                </a>
            </div>
        </form>
    </div>

    <!-- Resumen Mensual -->
    @if(!empty($monthlySummary))
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Resumen Mensual</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-500">Total sin IVA</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ number_format(array_sum(array_column($monthlySummary, 'total_without_vat')), 2, ',', '.') }} €
                </div>
            </div>
            <div class="rounded-xl bg-brand-50 p-4">
                <div class="text-xs font-semibold text-brand-700">Total con IVA</div>
                <div class="mt-1 text-2xl font-semibold text-brand-700">
                    {{ number_format(array_sum(array_column($monthlySummary, 'total_with_vat')), 2, ',', '.') }} €
                </div>
            </div>
        </div>
        
        @if(count($monthlySummary) > 1)
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2 text-right">Banco</th>
                        <th class="px-3 py-2 text-right">Efectivo</th>
                        <th class="px-3 py-2 text-right">Sin IVA</th>
                        <th class="px-3 py-2 text-right">Con IVA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthlySummary as $summary)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-semibold">{{ $summary['store_name'] }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format($summary['total_bank_amount'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format($summary['total_cash_amount'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ number_format($summary['total_without_vat'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold text-brand-700 whitespace-nowrap">{{ number_format($summary['total_with_vat'], 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    <!-- Tabla de Ventas Declaradas -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Ventas Declaradas</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2 text-right">Banco</th>
                        <th class="px-3 py-2 text-right">Efectivo</th>
                        <th class="px-3 py-2 text-right">Total sin IVA</th>
                        <th class="px-3 py-2 text-right">Total con IVA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($declaredSales as $sale)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <div class="font-semibold">{{ $sale->date->format('d/m/Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $sale->date->format('F Y') }}</div>
                            </td>
                            <td class="px-3 py-2 font-semibold">{{ $sale->store ? $sale->store->name : 'Sin tienda' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format($sale->bank_amount, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                {{ number_format($sale->cash_amount, 2, ',', '.') }} €
                                @if($sale->cash_reduction_percent > 0)
                                    <div class="text-xs text-slate-500">(-{{ number_format($sale->cash_reduction_percent, 2, ',', '.') }}%)</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ number_format($sale->total_without_vat, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold text-brand-700 whitespace-nowrap">{{ number_format($sale->total_with_vat, 2, ',', '.') }} €</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">
                                No hay ventas declaradas para el período seleccionado.
                                @if(auth()->user()->hasPermission('declared_sales.main.create'))
                                    <div class="mt-2">
                                        <form method="POST" action="{{ route('declared-sales.generate-from-daily-closes') }}" class="inline" onsubmit="return confirm('¿Generar ventas declaradas desde los cierres diarios del mes seleccionado?')">
                                            @csrf
                                            <input type="hidden" name="month" value="{{ request('month', now()->format('Y-m')) }}">
                                            @if(request('store') && request('store') !== 'all')
                                                <input type="hidden" name="store_id" value="{{ request('store') }}">
                                            @endif
                                            <button type="submit" class="text-brand-600 hover:text-brand-700 underline">
                                                Generar desde cierres diarios
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
