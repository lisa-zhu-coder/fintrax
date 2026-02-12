@extends('layouts.app')

@section('title', 'Horas extras — ' . $employee->full_name)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('overtime.index') }}" class="hover:text-brand-600">Horas extras</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $employee->full_name }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $employee->full_name }}</h1>
                <p class="text-sm text-slate-500">Historial de registros. Precio hora extras: {{ number_format($priceOvertime, 2, ',', '.') }} € — Precio hora domingo/festivo: {{ number_format($priceSunday, 2, ',', '.') }} €</p>
            </div>
            @php $store = $employee->stores()->first(); @endphp
            @if($store)
                <a href="{{ route('overtime.month', ['store' => $store, 'year' => now()->year, 'month' => now()->month]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Mes actual</a>
            @endif
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left">Fecha</th>
                    <th class="px-3 py-2 text-right">Horas extras</th>
                    <th class="px-3 py-2 text-right">Horas domingo/festivos</th>
                    <th class="px-3 py-2 text-right">Precio aplicado</th>
                    <th class="px-3 py-2 text-right">Importe calculado</th>
                    <th class="px-3 py-2 w-28"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                    @php
                        $precioAplicado = ($r->record->overtime_hours > 0 ? $priceOvertime . ' € (extras)' : '') . ($r->record->sunday_holiday_hours > 0 ? ($r->record->overtime_hours > 0 ? ' + ' : '') . $priceSunday . ' € (dom/fest)' : '');
                        if (empty(trim($precioAplicado))) $precioAplicado = '—';
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2">{{ $r->record->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($r->record->overtime_hours, 2, ',', '.') }} h</td>
                        <td class="px-3 py-2 text-right">{{ number_format($r->record->sunday_holiday_hours, 2, ',', '.') }} h</td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ $precioAplicado }}</td>
                        <td class="px-3 py-2 text-right font-medium">{{ number_format($r->amount_total, 2, ',', '.') }} €</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1">
                                <a href="{{ route('overtime.records.edit', $r->record) }}" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                <form method="POST" action="{{ route('overtime.records.destroy', $r->record) }}" class="inline" onsubmit="return confirm('¿Eliminar este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-slate-500">No hay registros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
