@extends('layouts.app')

@section('title', $inventory->name . ' – ' . $category->name)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('inventory.categories.index', ['year' => $inventory->year, 'month' => $inventory->month]) }}" class="text-sm text-slate-500 hover:text-slate-700 mb-1 inline-block">← Inventarios</a>
                <a href="{{ route('inventory.categories.inventories', [$category, 'year' => $inventory->year, 'month' => $inventory->month]) }}" class="text-sm text-slate-500 hover:text-slate-700 mb-1 inline-block">{{ $monthNames[$inventory->month] ?? $inventory->month }} {{ $inventory->year }} → {{ $category->name }}</a>
                <h1 class="text-lg font-semibold">{{ $inventory->name }}</h1>
                <p class="text-xs text-slate-500">{{ $monthNames[$inventory->month] ?? $inventory->month }} {{ $inventory->year }}@if($inventory->week_number) – Semana {{ $inventory->week_number }}@endif</p>
            </div>
            @if(auth()->user()->hasPermission('inventory.products.create') && $lines->isNotEmpty())
            <button type="button" onclick="document.getElementById('add-purchase-modal').classList.remove('hidden')" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Añadir compra
            </button>
            @endif
        </div>
    </header>

    @if(auth()->user()->hasPermission('inventory.products.create') && $lines->isNotEmpty())
    <div id="add-purchase-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" onclick="document.getElementById('add-purchase-modal').classList.add('hidden')"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold mb-4">Añadir compra</h2>
                <form method="POST" action="{{ route('inventory.categories.add-purchase', [$category, $inventory]) }}" id="add-purchase-form">
                    @csrf
                    <div class="mb-4">
                        <label for="purchase_date" class="block text-sm font-medium text-slate-700 mb-1">Fecha</label>
                        <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required class="add-purchase-input w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"/>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Productos y cantidades</label>
                        <div class="max-h-60 overflow-y-auto space-y-2 rounded-xl border border-slate-200 p-3">
                            @foreach($lines as $line)
                            <div class="flex items-center justify-between gap-2">
                                <label for="line_{{ $line->id }}" class="text-sm font-medium text-slate-700 flex-1">{{ $line->product->name }}</label>
                                <input type="number" name="lines[{{ $line->id }}][quantity]" id="line_{{ $line->id }}" value="0" min="0" class="add-purchase-input w-20 rounded border border-slate-200 px-2 py-1 text-right text-sm"/>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="document.getElementById('add-purchase-modal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar compra</button>
                    </div>
                </form>
                <script>
                (function() {
                    var form = document.getElementById('add-purchase-form');
                    if (!form) return;
                    var inputs = form.querySelectorAll('.add-purchase-input');
                    form.addEventListener('keydown', function(e) {
                        if (e.key !== 'Enter') return;
                        if (!e.target.classList.contains('add-purchase-input')) return;
                        e.preventDefault();
                        var idx = Array.prototype.indexOf.call(inputs, e.target);
                        if (idx >= 0 && idx < inputs.length - 1) inputs[idx + 1].focus();
                        else form.querySelector('button[type="submit"]').focus();
                    });
                })();
                </script>
            </div>
        </div>
    </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <form method="POST" action="{{ route('inventory.categories.update', [$category, $inventory]) }}">
            @csrf
            @method('PUT')
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-right">Cant. inicial</th>
                        <th class="px-3 py-2 text-right">Cant. adquirida</th>
                        <th class="px-3 py-2 text-right">Cant. usada</th>
                        <th class="px-3 py-2 text-right">Cant. vendida</th>
                        <th class="px-3 py-2 text-right">Cant. esperada</th>
                        <th class="px-3 py-2 text-right">Cant. real</th>
                        <th class="px-3 py-2 text-right">Discrepancia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($lines as $line)
                    <tr class="hover:bg-slate-50 group/row">
                        <td class="px-3 py-2 font-medium">
                            <button type="button" onclick="document.getElementById('history-modal-{{ $line->id }}').classList.remove('hidden')" class="text-left hover:text-brand-600 hover:underline cursor-pointer flex items-center gap-1">
                                {{ $line->product->name }}
                                @if($line->purchaseRecords->isNotEmpty())
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </button>
                        </td>
                        <td class="px-3 py-2 text-right">
                            @if(auth()->user()->hasPermission('inventory.products.edit'))
                            <input type="number" name="lines[{{ $line->id }}][initial_quantity]" value="{{ $line->initial_quantity }}" min="0" class="w-16 rounded border border-slate-200 px-2 py-1 text-right text-sm"/>
                            @else
                            {{ number_format($line->initial_quantity, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">{{ number_format($line->acquired_quantity, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($line->used_quantity, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($line->sold_quantity, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right font-medium">{{ number_format($line->expected_quantity, 0, ',', '.') }}</td>
                        <td class="px-3 py-2">
                            @if(auth()->user()->hasPermission('inventory.products.edit'))
                            <input type="number" name="lines[{{ $line->id }}][real_quantity]" value="{{ $line->real_quantity }}" min="0" class="w-20 rounded border border-slate-200 px-2 py-1 text-right text-sm"/>
                            @else
                            {{ number_format($line->real_quantity, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-medium {{ $line->discrepancy != 0 ? 'text-rose-600' : '' }}">{{ number_format($line->discrepancy, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if(auth()->user()->hasPermission('inventory.products.edit') && $lines->isNotEmpty())
            <div class="mt-4 flex justify-end">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar cambios</button>
            </div>
            @endif
        </form>
    </div>

    @foreach($lines as $line)
    <div id="history-modal-{{ $line->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" onclick="document.getElementById('history-modal-{{ $line->id }}').classList.add('hidden')"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold mb-4">Historial de adquisiciones – {{ $line->product->name }}</h2>
                @if($line->purchaseRecords->isEmpty())
                <p class="text-sm text-slate-500">No hay registros de compras.</p>
                @else
                <div class="max-h-64 overflow-y-auto space-y-2">
                    @foreach($line->purchaseRecords as $record)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 px-3 py-2 text-sm">
                        <div>
                            <span class="font-medium">{{ $record->purchase_date->format('d/m/Y') }}</span>
                            <span class="text-slate-600"> – {{ number_format($record->quantity, 0, ',', '.') }} ud.</span>
                        </div>
                        <span class="text-xs text-slate-500">{{ $record->user?->name ?? 'Sistema' }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="mt-4 flex justify-end">
                    <button type="button" onclick="document.getElementById('history-modal-{{ $line->id }}').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-base font-semibold mb-4">Resumen</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Total cantidad esperada</p>
                <p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($totals['expected'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Total cantidad real</p>
                <p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($totals['real'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Total discrepancia</p>
                <p class="mt-1 text-xl font-bold {{ $totals['discrepancy'] != 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ number_format($totals['discrepancy'], 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
