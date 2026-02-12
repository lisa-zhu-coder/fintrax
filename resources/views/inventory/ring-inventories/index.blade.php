@extends('layouts.app')

@section('title', 'Inventario de anillos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Inventario de anillos</h1>
                <p class="text-sm text-slate-500">Resumen por tienda (solo registros de cierre)</p>
            </div>
        </div>
    </header>

    <!-- Filtro por año -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('ring-inventories.index') }}" class="flex flex-wrap items-end gap-4">
            <label class="block min-w-[120px]">
                <span class="text-xs font-semibold text-slate-700">Año</span>
                <select name="year" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Filtrar</button>
        </form>
    </div>

    <!-- Tabla de tiendas -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @if($stores->isEmpty())
            <p class="text-slate-600">No hay tiendas. Crea al menos una en Empresa.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Tienda</th>
                            <th class="px-3 py-2 text-right">Anillos vendidos</th>
                            <th class="px-3 py-2 text-right">Taras</th>
                            <th class="px-3 py-2 text-right">Discrepancia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($stores as $store)
                            @php
                                $totals = $storeTotals[$store->id] ?? ['sold' => 0, 'tara' => 0, 'discrepancy' => 0];
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <a href="{{ route('ring-inventories.store-months', ['store' => $store, 'year' => $year]) }}" class="font-semibold text-slate-900 hover:text-brand-600">
                                        {{ $store->name }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-right">{{ number_format($totals['sold'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($totals['tara'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right font-medium {{ $totals['discrepancy'] != 0 ? 'text-rose-600' : '' }}">{{ number_format($totals['discrepancy'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
