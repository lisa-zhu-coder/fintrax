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
            </div>
            <a href="{{ route('objectives.index', ['year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Tiendas</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Mes</th>
                        @foreach($definitions as $def)
                            <th class="px-3 py-2 text-right">{{ $def->name }}</th>
                        @endforeach
                        <th class="px-3 py-2 text-right">Objetivo cumplido</th>
                        @foreach($definitions as $def)
                            <th class="px-3 py-2 text-right">Dif. {{ $def->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthsData as $m)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('objectives.month', ['store' => $store, 'year' => $m->year, 'month' => $m->month]) }}" class="font-semibold text-slate-900 hover:text-brand-600">{{ $m->monthName }}</a>
                            </td>
                            @foreach($m->objectives ?? [] as $obj)
                                <td class="px-3 py-2 text-right">{{ number_format($obj, 2, ',', '.') }} €</td>
                            @endforeach
                            <td class="px-3 py-2 text-right">{{ number_format($m->cumplido ?? 0, 2, ',', '.') }} €</td>
                            @foreach($m->diffs ?? [] as $diff)
                                <td class="px-3 py-2 text-right font-medium {{ $diff >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($diff, 2, ',', '.') }} €</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
