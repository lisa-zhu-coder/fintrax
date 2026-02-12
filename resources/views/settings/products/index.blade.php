@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold">Productos</h1>
                <p class="text-sm text-slate-500">Los productos se crean aquí. El inventario se genera automáticamente a partir de ellos.</p>
            </div>
            @if(auth()->user()->hasPermission('settings.products.create'))
            <a href="{{ route('product-settings.create') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Crear producto</a>
            @endif
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-base font-semibold">Categorías</h2>
            @if(auth()->user()->hasPermission('settings.products.create'))
            <form method="POST" action="{{ route('product-settings.categories.store') }}" class="flex gap-2">
                @csrf
                <input type="text" name="name" placeholder="Nueva categoría (ej. Bebidas)" required class="rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Crear categoría</button>
            </form>
            @endif
        </div>

        @if($categories->isEmpty())
            <p class="text-slate-600">No hay categorías. Crea una categoría y luego productos.</p>
        @else
            <div class="space-y-4">
                @foreach($categories as $category)
                <details class="group rounded-xl border border-slate-200 bg-slate-50/50" open="{{ $loop->first }}">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100/80 [&::-webkit-details-marker]:hidden">
                        <span>{{ $category->name }}</span>
                        <span class="text-xs text-slate-500">{{ $category->products->count() }} producto(s)</span>
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="border-t border-slate-200 bg-white px-4 py-3">
                        @if($category->products->isEmpty())
                            <p class="text-sm text-slate-500">Sin productos en esta categoría.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach($category->products as $product)
                                <li class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                                    <div>
                                        <span class="font-medium">{{ $product->name }}</span>
                                        <span class="ml-2 flex flex-wrap gap-1">
                                            @if($product->is_sellable)<span class="rounded px-2 py-0.5 text-xs bg-emerald-100 text-emerald-700">Venta</span>@endif
                                            @if($product->is_ingredient)<span class="rounded px-2 py-0.5 text-xs bg-blue-100 text-blue-700">Ingrediente</span>@endif
                                            @if($product->is_composed)<span class="rounded px-2 py-0.5 text-xs bg-amber-100 text-amber-700">Compuesto</span>@endif
                                            @if(!$product->is_sellable && !$product->is_ingredient && !$product->is_composed)<span class="text-xs text-slate-500">—</span>@endif
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(auth()->user()->hasPermission('settings.products.edit'))
                                        <a href="{{ route('product-settings.edit', $product) }}" class="text-sm text-brand-600 hover:text-brand-700">Editar</a>
                                        @endif
                                        @if(auth()->user()->hasPermission('settings.products.delete'))
                                        <form method="POST" action="{{ route('product-settings.destroy', $product) }}" class="inline" onsubmit="return confirm('¿Eliminar este producto?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-rose-600 hover:text-rose-700">Eliminar</button>
                                        </form>
                                        @endif
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </details>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
