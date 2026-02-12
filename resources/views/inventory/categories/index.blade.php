@extends('layouts.app')

@section('title', 'Inventarios')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h1 class="text-lg font-semibold">Inventarios</h1>
        <p class="text-sm text-slate-500">Selecciona año y mes para ver las categorías de inventario.</p>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('inventory.categories.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="year" class="block text-sm font-medium text-slate-700 mb-1">Año</label>
                <select name="year" id="year" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ ($year ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="month" class="block text-sm font-medium text-slate-700 mb-1">Mes</label>
                <select name="month" id="month" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @foreach($monthNames ?? [] as $m => $name)
                    <option value="{{ $m }}" {{ ($month ?? now()->month) == $m ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Ver inventarios</button>
        </form>
    </div>

    @if($showCategories ?? false)
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-base font-semibold mb-4">Inventarios – {{ $monthNames[$month] ?? $month }} {{ $year }}</h2>
        @if($categories->isEmpty())
            <p class="text-slate-600">No hay categorías. Crea categorías y productos en Ajustes → Productos.</p>
        @else
            <div class="space-y-3">
                @foreach($categories as $category)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 p-4">
                    <span class="font-semibold text-slate-800">{{ $category->name }}</span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('inventory.categories.inventories', [$category, 'year' => $year, 'month' => $month]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Ver inventarios
                        </a>
                        @if(auth()->user()->hasPermission('inventory.products.create'))
                        <a href="{{ route('inventory.categories.create', [$category, 'year' => $year, 'month' => $month]) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            Añadir inventario semanal
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif
</div>
@endsection
