@extends('layouts.app')

@section('title', 'Control de Efectivo')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Control de Efectivo</h1>
                <p class="text-sm text-slate-500">Efectivo retirado de los cierres de caja</p>
            </div>
            <div>
                <a href="{{ route('financial.cash-withdrawals.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Recoger efectivo
                </a>
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('financial.cash-control') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Año</span>
                    <select name="year" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @if(isset($availableYears) && is_array($availableYears))
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        @endif
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

    <!-- Lista de tiendas con saldos -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @if(empty($storesData))
            <div class="py-8 text-center text-slate-500">
                No hay registros de efectivo retirado
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Tienda</th>
                            <th class="px-3 py-2 text-right">Efectivo Retirado</th>
                            <th class="px-3 py-2 text-right">Efectivo Real</th>
                            <th class="px-3 py-2 text-right">Efectivo Recogido</th>
                            <th class="px-3 py-2 text-right">Gastos del Mes</th>
                            <th class="px-3 py-2 text-right">Traspasos efectivo</th>
                            <th class="px-3 py-2 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($storesData as $store)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <a href="{{ route('financial.cash-control-store', ['store' => $store['id']] + request()->except(['store'])) }}" class="font-semibold text-slate-900 hover:text-brand-600">
                                        {{ $store['name'] }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="font-semibold {{ $store['total'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($store['total'], 2, ',', '.') }} €
                                    </span>
                                    <span class="text-xs text-slate-500 ml-1">({{ $store['days_withdrawn'] }} días)</span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="font-semibold {{ $store['cash_real'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($store['cash_real'], 2, ',', '.') }} €
                                    </span>
                                    <span class="text-xs text-slate-500 ml-1">({{ $store['days_real'] }} días)</span>
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-amber-700">
                                    {{ number_format($store['cash_collected'] ?? 0, 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-rose-700">
                                    {{ number_format($store['month_expenses'], 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right font-semibold {{ ($store['total_traspasos_efectivo'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($store['total_traspasos_efectivo'] ?? 0, 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right font-semibold {{ $store['balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($store['balance'], 2, ',', '.') }} €
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
