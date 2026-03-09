@extends('layouts.app')

@section('title', 'Tipos de préstamo')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <nav class="text-xs text-slate-500 mb-1">
                <span class="text-slate-700">Ajustes</span>
                <span class="mx-1">/</span>
                <span>Préstamos</span>
                <span class="mx-1">/</span>
                <span>Tipos de préstamo</span>
            </nav>
            <h1 class="text-lg font-semibold">Tipos de préstamo</h1>
            <p class="text-sm text-slate-500">Define los tipos de préstamo (financiero/comercial, con o sin intereses y comisión de apertura) para clasificar los préstamos.</p>
        </div>
    </header>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('loan-types-settings.store') }}" class="mb-6 flex flex-wrap items-end gap-4">
            @csrf
            <label class="min-w-[180px]">
                <span class="block text-xs font-semibold text-slate-700 mb-1">Nombre</span>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Préstamo bancario"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <label class="min-w-[160px]">
                <span class="block text-xs font-semibold text-slate-700 mb-1">Categoría</span>
                <select name="category" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="financial" {{ old('category') === 'financial' ? 'selected' : '' }}>Financiero</option>
                    <option value="commercial" {{ old('category') === 'commercial' ? 'selected' : '' }}>Comercial</option>
                </select>
            </label>
            <label class="flex items-center gap-2 pt-6">
                <input type="hidden" name="has_interest" value="0"/>
                <input type="checkbox" name="has_interest" value="1" {{ old('has_interest', true) ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"/>
                <span class="text-sm font-medium text-slate-700">Tiene intereses</span>
            </label>
            <label class="flex items-center gap-2 pt-6">
                <input type="hidden" name="has_opening_fee" value="0"/>
                <input type="checkbox" name="has_opening_fee" value="1" {{ old('has_opening_fee') ? 'checked' : '' }} class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"/>
                <span class="text-sm font-medium text-slate-700">Tiene comisión de apertura</span>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Añadir tipo</button>
        </form>

        <h2 class="text-sm font-semibold text-slate-800 mb-3">Listado de tipos</h2>
        @if($loanTypes->isEmpty())
            <p class="text-slate-600">No hay tipos de préstamo. Crea al menos uno para poder usarlos al registrar préstamos.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-tl">Nombre</th>
                            <th class="px-3 py-2">Categoría</th>
                            <th class="px-3 py-2">Intereses</th>
                            <th class="px-3 py-2">Com. apertura</th>
                            <th class="px-3 py-2 rounded-tr"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($loanTypes as $type)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-3 py-2">
                                <form method="POST" action="{{ route('loan-types-settings.update', $type) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ old('name.' . $type->id, $type->name) }}" required maxlength="255"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm w-48 outline-none ring-brand-200 focus:ring-2"/>
                                    <input type="hidden" name="category" value="{{ $type->category }}"/>
                                    <input type="hidden" name="has_interest" value="{{ $type->has_interest ? '1' : '0' }}"/>
                                    <input type="hidden" name="has_opening_fee" value="{{ $type->has_opening_fee ? '1' : '0' }}"/>
                                    <button type="submit" class="rounded-lg bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-300">Guardar</button>
                                </form>
                            </td>
                            <td class="px-3 py-2">{{ $type->category === 'financial' ? 'Financiero' : 'Comercial' }}</td>
                            <td class="px-3 py-2">{{ $type->has_interest ? 'Sí' : 'No' }}</td>
                            <td class="px-3 py-2">{{ $type->has_opening_fee ? 'Sí' : 'No' }}</td>
                            <td class="px-3 py-2">
                                @if($type->isInUse())
                                    <span class="text-xs text-slate-400" title="En uso por uno o más préstamos">En uso</span>
                                @else
                                <form method="POST" action="{{ route('loan-types-settings.destroy', $type) }}" class="inline" onsubmit="return confirm('¿Eliminar este tipo de préstamo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" title="Eliminar">Eliminar</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
