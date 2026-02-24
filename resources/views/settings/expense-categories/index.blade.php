@extends('layouts.app')

@section('title', 'Categorías de gastos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <nav class="text-xs text-slate-500 mb-1">
                <span class="text-slate-700">Ajustes</span>
                <span class="mx-1">/</span>
                <span>Categorías de gastos</span>
            </nav>
            <h1 class="text-lg font-semibold">Categorías de gastos</h1>
            <p class="text-sm text-slate-500">Gestiona las categorías para clasificar los gastos en registros y cierres. Cada empresa tiene sus propias categorías.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if(auth()->user()->hasPermission('settings.expense_categories.create'))
        <form method="POST" action="{{ route('expense-categories-settings.store') }}" class="mb-6 flex flex-wrap items-end gap-3">
            @csrf
            <label class="min-w-[200px]">
                <span class="block text-xs font-semibold text-slate-700 mb-1">Nueva categoría</span>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Alquiler, Suministros..."
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Añadir categoría</button>
        </form>
        @endif

        <h2 class="text-sm font-semibold text-slate-800 mb-3">Listado de categorías</h2>
        @if($categories->isEmpty())
            <p class="text-slate-600">No hay categorías de gastos. Añade al menos una para poder usarlas en registros y gastos.</p>
        @else
            <ul class="space-y-2">
                @foreach($categories as $category)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
                    @if(auth()->user()->hasPermission('settings.expense_categories.edit'))
                    <form method="POST" action="{{ route('expense-categories-settings.update', $category) }}" class="flex flex-1 min-w-0 items-center gap-3">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ old('name.' . $category->id, $category->name) }}" required maxlength="255"
                            class="flex-1 min-w-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        <button type="submit" class="shrink-0 rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-300">Guardar</button>
                    </form>
                    @else
                    <span class="font-medium text-slate-800">{{ $category->name }}</span>
                    @endif
                    @if(auth()->user()->hasPermission('settings.expense_categories.delete'))
                    <form method="POST" action="{{ route('expense-categories-settings.destroy', $category) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" title="Eliminar">Eliminar</button>
                    </form>
                    @endif
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
