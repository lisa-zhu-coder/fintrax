@extends('layouts.app')

@section('title', 'Control de Efectivo - ' . $store->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('financial.cash-control', request()->except(['store'])) }}" class="text-brand-600 hover:text-brand-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-lg font-semibold">Control de Efectivo - {{ $store->name }}</h1>
                </div>
                <p class="text-sm text-slate-500">Efectivo retirado por mes</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-500">Saldo total</div>
                <div class="text-lg font-semibold {{ $storeTotal >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ number_format($storeTotal, 2, ',', '.') }} €
                </div>
            </div>
        </div>
    </header>

    <!-- Lista de meses -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @if(empty($monthsData))
            <div class="py-8 text-center text-slate-500">
                No hay registros de efectivo retirado para esta tienda
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Mes</th>
                            <th class="px-3 py-2 text-right">Efectivo Retirado</th>
                            <th class="px-3 py-2 text-right">Efectivo Real</th>
                            <th class="px-3 py-2 text-right">Efectivo Recogido</th>
                            <th class="px-3 py-2 text-right">Gastos del Mes</th>
                            <th class="px-3 py-2 text-right">Traspasos efectivo</th>
                            <th class="px-3 py-2 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($monthsData as $month)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <a href="{{ route('financial.cash-control-month', ['store' => $store->id, 'month' => $month['key']] + request()->except(['store', 'month'])) }}" class="font-semibold text-slate-900 hover:text-brand-600">
                                        {{ $month['label'] }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="font-semibold {{ $month['total'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($month['total'], 2, ',', '.') }} €
                                    </span>
                                    <span class="text-xs text-slate-500 ml-1">({{ $month['days_withdrawn'] }} días)</span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="font-semibold {{ $month['cash_real'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($month['cash_real'], 2, ',', '.') }} €
                                    </span>
                                    <span class="text-xs text-slate-500 ml-1">({{ $month['days_real'] }} días)</span>
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-amber-700">
                                    {{ number_format($month['cash_collected'] ?? 0, 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-rose-700">
                                    {{ number_format($month['month_expenses'], 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right font-semibold {{ ($month['traspasos_efectivo'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($month['traspasos_efectivo'] ?? 0, 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right font-semibold {{ $month['balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($month['balance'], 2, ',', '.') }} €
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
