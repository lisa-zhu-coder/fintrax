@extends('layouts.app')

@section('title', 'Añadir Proveedor')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Añadir proveedor</h1>
                <p class="text-sm text-slate-500">Completa los datos del nuevo proveedor</p>
            </div>
            <a href="{{ route('suppliers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre *</span>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo / Categoría de gasto *</span>
                    <select name="expense_category_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona una categoría</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Los gastos de los pedidos de este proveedor se asignarán a esta categoría.</p>
                    @error('expense_category_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">CIF</span>
                    <input type="text" name="cif" value="{{ old('cif') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Dirección</span>
                    <input type="text" name="address" value="{{ old('address') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Teléfono</span>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('suppliers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
