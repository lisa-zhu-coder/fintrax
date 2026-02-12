@extends('layouts.app')

@section('title', 'Objetivos de ventas')

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
        <div>
            <h1 class="text-lg font-semibold">Objetivos de ventas</h1>
            <p class="text-sm text-slate-500">Porcentajes por mes (Objetivo 1 y Objetivo 2). Configuración general aplica a todas las tiendas; opcionalmente puedes definir valores por tienda.</p>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('objectives-settings.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
            <label class="block min-w-[120px]">
                <span class="text-xs font-semibold text-slate-700">Año</span>
                <select name="year" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block min-w-[200px]">
                <span class="text-xs font-semibold text-slate-700">Configuración para</span>
                <select name="store_id" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Todas las tiendas (general)</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ (string)$storeId === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </label>
            <input type="hidden" name="year" value="{{ $year }}" form="objectives-get-form"/>
        </form>
        <form id="objectives-get-form" method="GET" action="{{ route('objectives-settings.index') }}" class="hidden" aria-hidden="true">
            <input type="hidden" name="year" value="{{ $year }}"/>
            <input type="hidden" name="store_id" value="{{ $storeId ?? '' }}"/>
        </form>

        <form method="POST" action="{{ route('objectives-settings.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}"/>
            @if($storeId !== null)
                <input type="hidden" name="store_id" value="{{ $storeId }}"/>
            @else
                <input type="hidden" name="store_id" value=""/>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Mes</th>
                            <th class="px-3 py-2 text-right w-40">% Objetivo 1</th>
                            <th class="px-3 py-2 text-right w-40">% Objetivo 2</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($months as $monthKey => $monthName)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-medium">{{ $monthName }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" name="months[{{ $monthKey }}][percentage_objective_1]" value="{{ old("months.{$monthKey}.percentage_objective_1", $settingsByMonth[$monthKey]['percentage_objective_1']) }}" step="0.01" min="0" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-right text-sm outline-none ring-brand-200 focus:ring-4"/>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="months[{{ $monthKey }}][percentage_objective_2]" value="{{ old("months.{$monthKey}.percentage_objective_2", $settingsByMonth[$monthKey]['percentage_objective_2']) }}" step="0.01" min="0" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-right text-sm outline-none ring-brand-200 focus:ring-4"/>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-slate-500">Los porcentajes deben ser 0 o positivos. No se permiten valores negativos.</p>

            <div class="flex items-center justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection
