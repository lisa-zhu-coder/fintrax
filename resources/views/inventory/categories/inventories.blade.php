@extends('layouts.app')

@section('title', 'Inventarios – ' . $category->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <a href="{{ route('inventory.categories.index', ['year' => $year, 'month' => $month]) }}" class="text-sm text-slate-500 hover:text-slate-700 mb-1 inline-block">← Inventarios</a>
        <p class="text-xs text-slate-500 mb-1">{{ $monthNames[$month] ?? $month }} {{ $year }}</p>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ $category->name }}</h1>
            @if(auth()->user()->hasPermission('inventory.products.create'))
            <a href="{{ route('inventory.categories.create', [$category, 'year' => $year, 'month' => $month]) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Añadir inventario semanal
            </a>
            @endif
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($inventories->isEmpty())
            <p class="text-slate-600">No hay inventarios en esta categoría para {{ $monthNames[$month] ?? $month }} {{ $year }}.</p>
            @if(auth()->user()->hasPermission('inventory.products.create'))
            <a href="{{ route('inventory.categories.create', [$category, 'year' => $year, 'month' => $month]) }}" class="mt-4 inline-block text-brand-600 hover:text-brand-700">Crear primer inventario</a>
            @endif
        @else
            <div class="space-y-2">
                @foreach($inventories as $inv)
                <a href="{{ route('inventory.categories.show', [$category, $inv]) }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50 transition-colors">
                    <span class="font-medium">{{ $inv->name }}</span>
                    <span class="text-sm text-slate-500">Semana {{ $inv->week_number }}</span>
                </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
