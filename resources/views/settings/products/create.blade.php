@extends('layouts.app')

@section('title', 'Crear producto')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <a href="{{ route('product-settings.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-1 inline-block">← Productos</a>
        <h1 class="text-lg font-semibold">Crear producto</h1>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('product-settings.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"/>
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="product_category_id" class="block text-sm font-medium text-slate-700 mb-1">Categoría de inventario *</label>
                <select name="product_category_id" id="product_category_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">Selecciona categoría</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('product_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('product_category_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Roles del producto *</label>
                <p class="text-xs text-slate-500 mb-2">Un producto puede tener varios roles a la vez.</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_sellable" value="1" {{ old('is_sellable') ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600" id="is_sellable"/>
                        <span class="text-sm">Se puede vender directamente</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_ingredient" value="1" {{ old('is_ingredient') ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600" id="is_ingredient"/>
                        <span class="text-sm">Se usa como ingrediente</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_composed" value="1" {{ old('is_composed') ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600" id="is_composed"/>
                        <span class="text-sm">Es producto compuesto (consume ingredientes)</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="base_unit" class="block text-sm font-medium text-slate-700 mb-1">Unidad base del producto</label>
                <select name="base_unit" id="base_unit" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @foreach(\App\Http\Controllers\ProductSettingsController::UNITS as $u)
                    <option value="{{ $u }}" {{ old('base_unit', 'unidades') == $u ? 'selected' : '' }}>{{ $u }}</option>
                    @endforeach
                </select>
            </div>

            <div id="ingredient-fields" class="space-y-3 hidden">
                <h3 class="text-sm font-semibold text-slate-700">Unidades (para ingredientes)</h3>
                <p class="text-xs text-slate-500">Ejemplo: Stock kg, Consumo gr, Conversión 1 kg = 1000 gr</p>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Unidad de stock</label>
                        <select name="stock_unit" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">—</option>
                            @foreach(\App\Http\Controllers\ProductSettingsController::UNITS as $u)
                            <option value="{{ $u }}" {{ old('stock_unit') == $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Unidad de consumo</label>
                        <select name="consumption_unit" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            <option value="">—</option>
                            @foreach(\App\Http\Controllers\ProductSettingsController::UNITS as $u)
                            <option value="{{ $u }}" {{ old('consumption_unit') == $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Factor de conversión (ej: 1 kg = 1000 gr)</label>
                        <input type="number" name="conversion_factor" value="{{ old('conversion_factor') }}" min="0" step="0.0001" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"/>
                    </div>
                </div>
            </div>

            <div id="composite-fields" class="space-y-3 hidden">
                <h3 class="text-sm font-semibold text-slate-700">Ingredientes (producto compuesto)</h3>
                <p class="text-xs text-slate-500">Cantidad de cada ingrediente por 1 unidad vendida.</p>
                <div id="ingredients-container" class="space-y-2"></div>
                @if($ingredientProducts->isNotEmpty())
                <button type="button" onclick="addIngredientRow()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">+ Añadir ingrediente</button>
                @else
                <p class="text-xs text-slate-500">Crea primero productos marcados como ingrediente.</p>
                @endif
            </div>

            <div class="flex gap-2 pt-4">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Crear producto</button>
                <a href="{{ route('product-settings.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@php
    $ingredientProductsJson = $ingredientProducts->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values()->toJson();
@endphp
<script>
const ingredientProducts = @json($ingredientProductsJson);
let ingredientIndex = 0;

function addIngredientRow() {
    const container = document.getElementById('ingredients-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 rounded-xl border border-slate-200 p-2';
    div.innerHTML = `
        <select name="ingredients[${ingredientIndex}][ingredient_product_id]" required class="flex-1 rounded border border-slate-200 px-2 py-1 text-sm">
            <option value="">Ingrediente</option>
            ${ingredientProducts.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
        </select>
        <input type="number" name="ingredients[${ingredientIndex}][quantity_per_unit]" placeholder="Cant." required min="0" step="0.0001" class="w-24 rounded border border-slate-200 px-2 py-1 text-sm text-right"/>
        <select name="ingredients[${ingredientIndex}][unit]" class="w-20 rounded border border-slate-200 px-2 py-1 text-sm">
            @foreach(\App\Http\Controllers\ProductSettingsController::UNITS as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach
        </select>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-700">✕</button>
    `;
    container.appendChild(div);
    ingredientIndex++;
}

function toggleFieldsByType() {
    const isIngredient = document.getElementById('is_ingredient')?.checked;
    const isComposed = document.getElementById('is_composed')?.checked;
    document.getElementById('ingredient-fields').classList.toggle('hidden', !isIngredient);
    document.getElementById('composite-fields').classList.toggle('hidden', !isComposed);
}

document.querySelectorAll('#is_ingredient, #is_composed').forEach(r => {
    r.addEventListener('change', toggleFieldsByType);
});

document.addEventListener('DOMContentLoaded', function() {
    toggleFieldsByType();
});
</script>
@endsection
