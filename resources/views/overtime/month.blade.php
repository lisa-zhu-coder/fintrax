@extends('layouts.app')

@section('title', 'Horas extras — ' . $store->name . ' — ' . $monthName . ' ' . $year)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('overtime.index') }}" class="hover:text-brand-600">Horas extras</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('overtime.store-months', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $store->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $monthName }} {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $monthName }} {{ $year }}</h1>
                <p class="text-sm text-slate-500">Empleadas con registros en el mes. Pincha en una empleada para ver su historial.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('overtime.create', ['store' => $store, 'year' => $year, 'month' => $month]) }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Añadir horas
                </a>
                <a href="{{ route('overtime.store-months', ['store' => $store, 'year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Meses</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left">Empleada</th>
                    <th class="px-3 py-2 text-right">Horas extras</th>
                    <th class="px-3 py-2 text-right">Precio h. extras</th>
                    <th class="px-3 py-2 text-right">Importe horas extras</th>
                    <th class="px-3 py-2 text-right">Horas domingo/festivos</th>
                    <th class="px-3 py-2 text-right">Precio h. domingo/fest.</th>
                    <th class="px-3 py-2 text-right">Importe domingo/festivos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($employeesData as $data)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2">
                            <a href="{{ route('overtime.employee', $data->employee) }}" class="font-semibold text-slate-900 hover:text-brand-600">{{ $data->employee->full_name }}</a>
                        </td>
                        <td class="px-3 py-2 text-right">{{ number_format($data->hours_overtime, 2, ',', '.') }} h</td>
                        <td class="px-3 py-2 text-right">{{ number_format($data->price_overtime, 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right">{{ number_format($data->amount_overtime, 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right">{{ number_format($data->hours_sunday_holiday, 2, ',', '.') }} h</td>
                        <td class="px-3 py-2 text-right">{{ number_format($data->price_sunday_holiday, 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right">{{ number_format($data->amount_sunday_holiday, 2, ',', '.') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-slate-500">No hay registros en este mes. Usa «Añadir horas» para registrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
