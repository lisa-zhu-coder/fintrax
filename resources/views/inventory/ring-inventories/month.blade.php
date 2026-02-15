@extends('layouts.app')

@section('title', 'Inventario de anillos — ' . $store->name . ' — ' . $monthName . ' ' . $year)

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
                    <a href="{{ route('ring-inventories.store-months', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $store->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $monthName }} {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $monthName }} {{ $year }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ring-inventories.store-months', ['store' => $store, 'year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Meses</a>
                @if(auth()->user()->hasPermission('inventory.rings.create'))
                <a href="{{ route('ring-inventories.create', ['store_id' => $store->id, 'date' => sprintf('%04d-%02d-01', $year, $month)]) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">+ Nuevo registro</a>
                @endif
            </div>
        </div>
    </header>

    {{-- Resumen solo con registros de turno cierre (mismos valores que la tabla de meses) --}}
    <div class="rounded-2xl border-2 border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-100">
        <h2 class="text-sm font-semibold text-amber-900 mb-3">Resumen del mes (solo registros de cierre)</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <span class="text-xs text-slate-600">Anillos vendidos</span>
                <p class="text-xl font-semibold text-slate-900">{{ number_format($totalSold, 0, ',', '.') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-600">Taras</span>
                <p class="text-xl font-semibold text-slate-900">{{ number_format($totalTara ?? 0, 0, ',', '.') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-600">Discrepancia</span>
                <p class="text-xl font-semibold {{ $totalDiscrepancy != 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ number_format($totalDiscrepancy, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Turno</th>
                        <th class="px-3 py-2 text-right">Inicial</th>
                        <th class="px-3 py-2 text-right">Taras</th>
                        <th class="px-3 py-2 text-right">Vendida</th>
                        <th class="px-3 py-2 text-right">Final</th>
                        <th class="px-3 py-2 text-right">Discrepancia</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">{{ $record->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $record->shift === 'cierre' ? 'Cierre' : 'Cambio de turno' }}</td>
                            <td class="px-3 py-2 text-right">{{ $record->initial_quantity !== null ? number_format($record->initial_quantity, 0, ',', '.') : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $record->tara_quantity !== null ? number_format($record->tara_quantity, 0, ',', '.') : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $record->sold_quantity !== null ? number_format($record->sold_quantity, 0, ',', '.') : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $record->final_quantity !== null ? number_format($record->final_quantity, 0, ',', '.') : '—' }}</td>
                            <td class="px-3 py-2 text-right font-medium {{ $record->discrepancy != 0 ? 'text-rose-600' : '' }}">{{ number_format($record->discrepancy, 0, ',', '.') }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('ring-inventories.show', $record) }}" class="rounded-lg px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Ver</a>
                                    <a href="{{ route('ring-inventories.edit', $record) }}" class="rounded-lg px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50">Editar</a>
                                    <form method="POST" action="{{ route('ring-inventories.destroy', $record) }}" class="inline" onsubmit="return confirm('¿Eliminar este registro?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-slate-500">No hay registros en este mes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
