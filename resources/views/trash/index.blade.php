@extends('layouts.app')

@section('title', 'Papelera')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Papelera</h1>
                <p class="text-sm text-slate-500">Registros eliminados. Se eliminarán automáticamente después de 30 días si no se recuperan.</p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->hasPermission('trash.main.delete'))
                <form method="POST" action="{{ route('trash.empty') }}" onsubmit="return confirm('¿Estás seguro de que quieres vaciar la papelera? Esta acción no se puede deshacer.');">
                    @csrf
                    <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        Vaciar papelera
                    </button>
                </form>
                @endif
                <a href="{{ route('financial.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    ← Volver
                </a>
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('trash.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Tipo</span>
                <select name="type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Todos</option>
                    <option value="daily_close" {{ request('type') === 'daily_close' ? 'selected' : '' }}>Cierre diario</option>
                    <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Gasto</option>
                    <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Ingreso</option>
                    <option value="expense_refund" {{ request('type') === 'expense_refund' ? 'selected' : '' }}>Devolución</option>
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Tienda</span>
                <select name="store" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Todas</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Período (eliminado)</span>
                <select name="period" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="last_7" {{ ($period ?? '') === 'last_7' ? 'selected' : '' }}>Últimos 7 días</option>
                    <option value="last_30" {{ ($period ?? '') === 'last_30' ? 'selected' : '' }}>Últimos 30 días</option>
                    <option value="last_90" {{ ($period ?? '') === 'last_90' ? 'selected' : '' }}>Últimos 90 días</option>
                    <option value="this_month" {{ ($period ?? '') === 'this_month' ? 'selected' : '' }}>Este mes</option>
                    <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                    <option value="this_year" {{ ($period ?? '') === 'this_year' ? 'selected' : '' }}>Este año</option>
                    <option value="all" {{ ($period ?? '') === 'all' ? 'selected' : '' }}>Todos</option>
                </select>
            </label>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr class="whitespace-nowrap">
                        <th class="whitespace-nowrap px-3 py-2">Fecha</th>
                        <th class="whitespace-nowrap px-3 py-2">Tipo</th>
                        <th class="whitespace-nowrap px-3 py-2">Tienda</th>
                        <th class="whitespace-nowrap px-3 py-2">Concepto</th>
                        <th class="whitespace-nowrap px-3 py-2">Importe</th>
                        <th class="whitespace-nowrap px-3 py-2">Eliminado el</th>
                        <th class="whitespace-nowrap px-3 py-2">Días restantes</th>
                        <th class="whitespace-nowrap px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50 whitespace-nowrap">
                            <td class="whitespace-nowrap px-3 py-2">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-3 py-2">
                                @php
                                    $typeLabels = [
                                        'daily_close' => ['label' => 'Cierre diario', 'color' => 'bg-brand-50 text-brand-700 ring-brand-100'],
                                        'expense' => ['label' => 'Gasto', 'color' => 'bg-rose-50 text-rose-700 ring-rose-100'],
                                        'income' => ['label' => 'Ingreso', 'color' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
                                        'expense_refund' => ['label' => 'Devolución', 'color' => 'bg-amber-50 text-amber-700 ring-amber-100'],
                                    ];
                                    $typeInfo = $typeLabels[$entry->type] ?? $typeLabels['daily_close'];
                                @endphp
                                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 {{ $typeInfo['color'] }}">
                                    {{ $typeInfo['label'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2">{{ $entry->store->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-2">{{ $entry->concept ?? $entry->expense_concept ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-2 font-medium">{{ number_format($entry->amount ?? 0, 2, ',', '.') }} €</td>
                            <td class="whitespace-nowrap px-3 py-2">{{ $entry->deleted_at->format('d/m/Y H:i') }}</td>
                            <td class="whitespace-nowrap px-3 py-2">
                                @php
                                    $daysRemaining = (int) max(0, 30 - $entry->deleted_at->diffInDays(now()));
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $daysRemaining > 7 ? 'bg-emerald-100 text-emerald-800' : ($daysRemaining > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ $daysRemaining }} días
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end flex-nowrap">
                                    @if(auth()->user()->hasPermission('trash.main.edit'))
                                    <form method="POST" action="{{ route('trash.restore', $entry->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                            Restaurar
                                        </button>
                                    </form>
                                    @endif
                                    @if(auth()->user()->hasPermission('trash.main.delete'))
                                    <form method="POST" action="{{ route('trash.force-delete', $entry->id) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar permanentemente este registro?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                            Eliminar
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-slate-500">La papelera está vacía</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
