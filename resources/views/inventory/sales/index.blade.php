@extends('layouts.app')

@section('title', 'Ventas de productos')

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
            <h1 class="text-lg font-semibold">Ventas de productos</h1>
            @if(auth()->user()->hasPermission('sales.products.create'))
            <button type="button" onclick="document.getElementById('add-sales-modal').classList.remove('hidden')" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Añadir ventas
            </button>
            @endif
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('inventory.sales.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="year" class="block text-sm font-medium text-slate-700 mb-1">Año</label>
                <select name="year" id="year" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="month" class="block text-sm font-medium text-slate-700 mb-1">Mes</label>
                <select name="month" id="month" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @foreach($monthNames as $m => $name)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Filtrar</button>
        </form>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <h2 class="text-base font-semibold mb-4">{{ $monthNames[$month] ?? $month }} {{ $year }}</h2>
        @if($sellableProducts->isEmpty())
            <p class="text-slate-600">No hay productos de venta directa o compuestos. Crea productos en Ajustes → Productos.</p>
        @else
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        @foreach($weekNumbers as $wn)
                        <th class="px-3 py-2 text-right">Semana {{ $wn }}</th>
                        @endforeach
                        @if($weekNumbers->isEmpty())
                        <th class="px-3 py-2 text-right text-slate-400">Sin datos aún</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($sellableProducts as $product)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-medium">
                            {{ $product->name }}
                            @if($product->isComposite())
                            <span class="text-xs text-slate-500">(compuesto)</span>
                            @endif
                        </td>
                        @foreach($weekNumbers as $wn)
                        <td class="px-3 py-2 text-right">
                            {{ $salesByProductWeek[$product->id][$wn] ?? '–' }}
                        </td>
                        @endforeach
                        @if($weekNumbers->isEmpty())
                        <td class="px-3 py-2 text-right text-slate-400">–</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if(auth()->user()->hasPermission('sales.products.create') && $sellableProducts->isNotEmpty())
    <div id="add-sales-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" onclick="document.getElementById('add-sales-modal').classList.add('hidden')"></div>
            <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-semibold mb-4">Añadir ventas</h2>
                <form method="POST" action="{{ route('inventory.sales.store') }}" id="add-sales-form">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
                        <div>
                            <label for="week_number" class="block text-sm font-medium text-slate-700 mb-1">Semana *</label>
                            <select name="week_number" id="week_number" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="1">Semana 1</option>
                                <option value="2">Semana 2</option>
                                <option value="3">Semana 3</option>
                                <option value="4">Semana 4</option>
                            </select>
                        </div>
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Categoría de inventario *</label>
                            <select name="category_id" id="category_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Selecciona categoría</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="inventory_id" class="block text-sm font-medium text-slate-700 mb-1">Inventario activo *</label>
                        <select name="inventory_id" id="inventory_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Primero selecciona categoría y semana</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Cantidad vendida por producto</label>
                        <div class="max-h-60 overflow-y-auto space-y-2 rounded-xl border border-slate-200 p-3">
                            @foreach($sellableProducts as $product)
                            <div class="flex items-center justify-between gap-2">
                                <label for="qty_{{ $product->id }}" class="text-sm font-medium text-slate-700 flex-1">{{ $product->name }}</label>
                                <input type="number" name="sales[{{ $product->id }}][quantity_sold]" id="qty_{{ $product->id }}" value="0" min="0" class="w-24 rounded border border-slate-200 px-2 py-1 text-right text-sm"/>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="document.getElementById('add-sales-modal').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar ventas</button>
                    </div>
                </form>
                <script>
                (function() {
                    var catSelect = document.getElementById('category_id');
                    var invSelect = document.getElementById('inventory_id');
                    var weekSelect = document.getElementById('week_number');
                    var year = {{ $year }};
                    var month = {{ $month }};

                    function loadInventories() {
                        var catId = catSelect.value;
                        var week = weekSelect.value;
                        if (!catId) {
                            invSelect.innerHTML = '<option value="">Primero selecciona categoría</option>';
                            return;
                        }
                        invSelect.innerHTML = '<option value="">Cargando...</option>';
                        fetch('{{ url("inventory/sales/inventories") }}?category_id=' + catId + '&year=' + year + '&month=' + month + '&week_number=' + week)
                            .then(r => r.json())
                            .then(data => {
                                invSelect.innerHTML = '<option value="">Selecciona inventario</option>';
                                data.forEach(function(inv) {
                                    var opt = document.createElement('option');
                                    opt.value = inv.id;
                                    opt.textContent = inv.name + (inv.week_number ? ' (Semana ' + inv.week_number + ')' : '');
                                    invSelect.appendChild(opt);
                                });
                                if (data.length === 0) {
                                    invSelect.innerHTML = '<option value="">No hay inventarios para esta categoría/semana</option>';
                                }
                            })
                            .catch(function() {
                                invSelect.innerHTML = '<option value="">Error al cargar</option>';
                            });
                    }
                    catSelect.addEventListener('change', loadInventories);
                    weekSelect.addEventListener('change', loadInventories);
                })();
                </script>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
