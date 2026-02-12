@extends('layouts.app')

@section('title', 'Registro inventario anillos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('ring-inventories.index', ['year' => $ringInventory->date->year]) }}" class="hover:text-brand-600">Inventario de anillos</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('ring-inventories.store-months', ['store' => $ringInventory->store, 'year' => $ringInventory->date->year]) }}" class="hover:text-brand-600">{{ $ringInventory->store->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">Registro</span>
                </nav>
                <h1 class="text-lg font-semibold">Registro de inventario</h1>
                <p class="text-sm text-slate-500">{{ $ringInventory->store->name }} — {{ $ringInventory->date->format('d/m/Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ring-inventories.month', ['store' => $ringInventory->store, 'year' => $ringInventory->date->year, 'month' => $ringInventory->date->month]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Mes</a>
                <a href="{{ route('ring-inventories.edit', $ringInventory) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Editar</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold text-slate-500">Tienda</dt>
                <dd class="mt-1 font-medium">{{ $ringInventory->store->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Fecha</dt>
                <dd class="mt-1">{{ $ringInventory->date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Turno</dt>
                <dd class="mt-1">{{ $ringInventory->shift === 'cierre' ? 'Cierre' : 'Cambio de turno' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Cantidad inicial</dt>
                <dd class="mt-1">{{ $ringInventory->initial_quantity !== null ? number_format($ringInventory->initial_quantity, 0, ',', '.') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Taras</dt>
                <dd class="mt-1">{{ $ringInventory->tara_quantity !== null ? number_format($ringInventory->tara_quantity, 0, ',', '.') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Cantidad vendida</dt>
                <dd class="mt-1">{{ $ringInventory->sold_quantity !== null ? number_format($ringInventory->sold_quantity, 0, ',', '.') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Cantidad final</dt>
                <dd class="mt-1">{{ $ringInventory->final_quantity !== null ? number_format($ringInventory->final_quantity, 0, ',', '.') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Discrepancia</dt>
                <dd class="mt-1 font-medium {{ $ringInventory->discrepancy != 0 ? 'text-rose-600' : '' }}">{{ number_format($ringInventory->discrepancy, 0, ',', '.') }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
