@extends('layouts.app')

@section('title', 'Objetivos mensuales')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Objetivos mensuales</h1>
                <p class="text-sm text-slate-500">Comparativa por día de la semana (año actual vs anterior). Filtro por año.</p>
            </div>
            <a href="{{ route('objectives.import') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 16m4-4V4"/></svg>
                Importar CSV
            </a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('objectives.index') }}" class="flex flex-wrap items-end gap-4">
            <label class="block min-w-[120px]">
                <span class="text-xs font-semibold text-slate-700">Año</span>
                <select name="year" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Filtrar</button>
        </form>
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Tienda</th>
                        <th class="px-3 py-2 text-right">Objetivo 1</th>
                        <th class="px-3 py-2 text-right">Objetivo 2</th>
                        <th class="px-3 py-2 text-right">Objetivo cumplido</th>
                        <th class="px-3 py-2 text-right">Diferencia Obj. 1</th>
                        <th class="px-3 py-2 text-right">Diferencia Obj. 2</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($stores as $store)
                        @php $data = $storeData[$store->id] ?? ['obj1' => 0, 'obj2' => 0, 'cumplido' => 0, 'diff1' => 0, 'diff2' => 0]; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('objectives.store-months', ['store' => $store, 'year' => $year]) }}" class="font-semibold text-slate-900 hover:text-brand-600">{{ $store->name }}</a>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($data['obj1'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($data['obj2'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($data['cumplido'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-medium {{ $data['diff1'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($data['diff1'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-medium {{ $data['diff2'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($data['diff2'], 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
