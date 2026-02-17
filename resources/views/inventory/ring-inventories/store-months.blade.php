@extends('layouts.app')

@section('title', 'Inventario de anillos — ' . $store->name . ' — ' . $year)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('ring-inventories.index', ['year' => $year]) }}" class="hover:text-brand-600">Inventario de anillos</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $store->name }} — {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $year }}</h1>
                <p class="text-sm text-slate-500">Resumen por mes (solo registros de cierre). Mismos valores que el cuadro de resumen de cada mes.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ring-inventories.index', ['year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Tiendas</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Mes</th>
                        <th class="px-3 py-2 text-right">Anillos vendidos</th>
                        <th class="px-3 py-2 text-right">Taras</th>
                        <th class="px-3 py-2 text-right">Discrepancia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthsData as $m)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('ring-inventories.month', ['store' => $store, 'year' => $m->year, 'month' => $m->month]) }}" class="font-semibold text-slate-900 hover:text-brand-600">
                                    {{ $m->monthName }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->sold, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->tara, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right font-medium {{ $m->discrepancy != 0 ? 'text-rose-600' : '' }}">{{ number_format($m->discrepancy, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
