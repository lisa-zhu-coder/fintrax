@extends('layouts.app')

@section('title', 'Horas extras — ' . $store->name . ' — ' . $year)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('overtime.index') }}" class="hover:text-brand-600">Horas extras</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $store->name }} — {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $year }}</h1>
                <p class="text-sm text-slate-500">Resumen por mes. Pincha en un mes para ver empleadas.</p>
            </div>
            <a href="{{ route('overtime.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Tiendas</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Mes</th>
                        <th class="px-3 py-2 text-right">Total horas extras</th>
                        <th class="px-3 py-2 text-right">Total horas domingo/festivos</th>
                        <th class="px-3 py-2 text-right">Importe horas extras</th>
                        <th class="px-3 py-2 text-right">Importe domingo/festivos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthsData as $m)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('overtime.month', ['store' => $store, 'year' => $m->year, 'month' => $m->month]) }}" class="font-semibold text-slate-900 hover:text-brand-600">{{ $m->monthName }}</a>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->total_overtime_hours, 2, ',', '.') }} h</td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->total_sunday_holiday_hours, 2, ',', '.') }} h</td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->total_amount_overtime, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($m->total_amount_sunday_holiday, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
