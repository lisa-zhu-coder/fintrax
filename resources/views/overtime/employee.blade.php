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
                <p class="text-sm text-slate-500">Historial de registros por tipo de horas extras.</p>
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
                    <th class="px-3 py-2 text-left">Tipo</th>
                    <th class="px-3 py-2 text-right">Horas</th>
                    <th class="px-3 py-2 text-right">Precio/h</th>
                    <th class="px-3 py-2 text-right">Importe</th>
                    <th class="px-3 py-2 w-28"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2">{{ $r->record->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">{{ $r->record->overtimeType->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($r->record->hours, 2, ',', '.') }} h</td>
                        <td class="px-3 py-2 text-right">{{ number_format($r->price, 2, ',', '.') }} €</td>
                        <td class="px-3 py-2 text-right font-medium">{{ number_format($r->amount, 2, ',', '.') }} €</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1">
                                @if(auth()->user()->hasPermission('hr.overtime.edit'))
                                <a href="{{ route('overtime.records.edit', $r->record) }}" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                @endif
                                @if(auth()->user()->hasPermission('hr.overtime.delete'))
                                <form method="POST" action="{{ route('overtime.records.destroy', $r->record) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                                @endif
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
