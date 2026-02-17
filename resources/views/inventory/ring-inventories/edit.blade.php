@extends('layouts.app')

@section('title', 'Editar registro — Inventario de anillos')

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
                    <span class="text-slate-700">Editar</span>
                </nav>
                <h1 class="text-lg font-semibold">Editar registro</h1>
                <p class="text-sm text-slate-500">{{ $ringInventory->store->name }} — {{ $ringInventory->date->format('d/m/Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ring-inventories.show', $ringInventory) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Ver</a>
                <a href="{{ route('ring-inventories.month', ['store' => $ringInventory->store, 'year' => $ringInventory->date->year, 'month' => $ringInventory->date->month]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Mes</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('ring-inventories.update', $ringInventory) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                    <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id', $ringInventory->store_id) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', $ringInventory->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Turno *</span>
                    <select name="shift" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @foreach(\App\Models\RingInventory::shiftOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('shift', $ringInventory->shift) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Cantidad inicial</span>
                    <input type="number" name="initial_quantity" min="0" value="{{ old('initial_quantity', $ringInventory->initial_quantity) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Reposición</span>
                    <input type="number" name="replenishment_quantity" value="{{ old('replenishment_quantity', $ringInventory->replenishment_quantity) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="Puede ser + o -"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Taras</span>
                    <input type="number" name="tara_quantity" min="0" value="{{ old('tara_quantity', $ringInventory->tara_quantity) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Cantidad vendida</span>
                    <input type="number" name="sold_quantity" value="{{ old('sold_quantity', $ringInventory->sold_quantity) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="Puede ser + o -"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Cantidad final</span>
                    <input type="number" name="final_quantity" min="0" value="{{ old('final_quantity', $ringInventory->final_quantity) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Comentario</span>
                <textarea name="comment" rows="2" maxlength="2000" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="Opcional">{{ old('comment', $ringInventory->comment) }}</textarea>
            </label>
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('ring-inventories.show', $ringInventory) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
