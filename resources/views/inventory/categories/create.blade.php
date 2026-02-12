@extends('layouts.app')

@section('title', 'Crear inventario – ' . $category->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <a href="{{ route('inventory.categories.inventories', [$category, 'year' => $year, 'month' => $month]) }}" class="text-sm text-slate-500 hover:text-slate-700 mb-1 inline-block">← {{ $category->name }}</a>
        <p class="text-xs text-slate-500 mb-1">{{ $monthNames[$month] ?? $month }} {{ $year }}</p>
        <h1 class="text-lg font-semibold">Añadir inventario semanal</h1>
    </header>

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('inventory.categories.store', $category) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre del inventario *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="ej. Semana 2" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"/>
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="week_number" class="block text-sm font-medium text-slate-700 mb-1">Semana *</label>
                <select name="week_number" id="week_number" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">Selecciona semana</option>
                    <option value="1" {{ old('week_number') == 1 ? 'selected' : '' }}>Semana 1</option>
                    <option value="2" {{ old('week_number') == 2 ? 'selected' : '' }}>Semana 2</option>
                    <option value="3" {{ old('week_number') == 3 ? 'selected' : '' }}>Semana 3</option>
                    <option value="4" {{ old('week_number') == 4 ? 'selected' : '' }}>Semana 4</option>
                    <option value="5" {{ old('week_number') == 5 ? 'selected' : '' }}>Semana 5</option>
                </select>
                @error('week_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="source_inventory_id" class="block text-sm font-medium text-slate-700 mb-1">Procedencia de la cantidad inicial</label>
                <select name="source_inventory_id" id="source_inventory_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">Sin procedencia (cantidades iniciales manuales)</option>
                    @foreach($sourceInventories ?? [] as $src)
                    <option value="{{ $src->id }}" {{ old('source_inventory_id') == $src->id ? 'selected' : '' }}>
                        {{ $src->name }} – {{ $monthNames[$src->month] ?? $src->month }} {{ $src->year }}
                    </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Si eliges un inventario anterior, la cantidad real de ese inventario se copiará como cantidad inicial.</p>
            </div>
            <div class="flex gap-2 pt-4">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Crear inventario</button>
                <a href="{{ route('inventory.categories.inventories', [$category, 'year' => $year, 'month' => $month]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
