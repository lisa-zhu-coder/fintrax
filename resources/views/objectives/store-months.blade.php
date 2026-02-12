@extends('layouts.app')

@section('title', 'Objetivos — ' . $store->name . ' — ' . $year)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('objectives.index', ['year' => $year]) }}" class="hover:text-brand-600">Objetivos mensuales</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $store->name }} — {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $year }}</h1>
                <p class="text-sm text-slate-500">Valores por mes (suma de los diarios con el porcentaje del mes)</p>
            </div>
            <a href="{{ route('objectives.index', ['year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Tiendas</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <p class="text-sm text-slate-500 mb-4">Entra en un mes para introducir la base del año anterior y generar las filas diarias.</p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Mes</th>
                        <th class="px-3 py-2 text-right">Objetivo 1</th>
                        <th class="px-3 py-2 text-right">Objetivo 2</th>
                        <th class="px-3 py-2 text-right">Objetivo cumplido</th>
                        <th class="px-3 py-2 text-right">Dif. Obj. 1</th>
                        <th class="px-3 py-2 text-right">Dif. Obj. 2</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthsData as $m)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('objectives.month', ['store' => $store, 'year' => $m->year, 'month' => $m->month]) }}" class="font-semibold text-slate-900 hover:text-brand-600">{{ $m->monthName }}</a>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->obj1, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->obj2, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->cumplido, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-medium {{ $m->diff1 >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($m->diff1, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-medium {{ $m->diff2 >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($m->diff2, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
