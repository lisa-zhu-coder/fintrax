@extends('layouts.app')

@section('title', 'Objetivos — ' . $store->name . ' — ' . $monthName . ' ' . $year)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('objectives.index', ['year' => $year]) }}" class="hover:text-brand-600">Objetivos mensuales</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('objectives.store-months', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $store->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $monthName }} {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $monthName }} {{ $year }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" form="form-bases-mes" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar mes</button>
                <a href="{{ route('objectives.store-months', ['store' => $store, 'year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Meses</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">Resumen del mes</h2>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-medium text-slate-500 uppercase">Total Objetivo 1</p>
                <p class="text-lg font-semibold text-slate-800">{{ number_format($summary->total_obj1, 2, ',', '.') }} €</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-medium text-slate-500 uppercase">Total Objetivo 2</p>
                <p class="text-lg font-semibold text-slate-800">{{ number_format($summary->total_obj2, 2, ',', '.') }} €</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-medium text-slate-500 uppercase">Total Objetivo cumplido</p>
                <p class="text-lg font-semibold text-slate-800">{{ number_format($summary->total_cumplido, 2, ',', '.') }} €</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-medium text-slate-500 uppercase">Diferencia Obj. 1</p>
                <p class="text-lg font-semibold {{ $summary->diff1 >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($summary->diff1, 2, ',', '.') }} €</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-medium text-slate-500 uppercase">Diferencia Obj. 2</p>
                <p class="text-lg font-semibold {{ $summary->diff2 >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($summary->diff2, 2, ',', '.') }} €</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <form method="POST" action="{{ route('objectives.update-month-bases', ['store' => $store, 'year' => $year, 'month' => $month]) }}" id="form-bases-mes">
            @csrf
            @method('PUT')
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Fecha 2025 (día)</th>
                        <th class="px-3 py-2 text-right">Base 2025</th>
                        <th class="px-3 py-2 text-left">Fecha 2026 (día)</th>
                        <th class="px-3 py-2 text-right">Objetivo 1</th>
                        <th class="px-3 py-2 text-right">Objetivo 2</th>
                        <th class="px-3 py-2 text-right">Objetivo cumplido 2026</th>
                        <th class="px-3 py-2 text-right">Dif. Obj. 1</th>
                        <th class="px-3 py-2 text-right">Dif. Obj. 2</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($rowsWithCalcs as $item)
                        @php $row = $item->row; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <span class="block font-medium text-slate-700">{{ $row->date_2025->locale('es')->dayName }}</span>
                                <span class="block text-sm text-slate-500">{{ $row->date_2025->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" name="bases[{{ $row->id }}]" value="{{ $row->base_2025 }}" step="0.01" min="0" class="base-2025-input w-24 rounded-lg border border-slate-200 px-2 py-1 text-right text-sm" onfocus="this.select()"/>
                            </td>
                            <td class="px-3 py-2">
                                <span class="block font-medium text-slate-700">{{ $row->date_2026->locale('es')->dayName }}</span>
                                <span class="block text-sm text-slate-500">{{ $row->date_2026->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->obj1, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->obj2, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->cumplido, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-medium {{ $item->diff1 >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($item->diff1, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-medium {{ $item->diff2 >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($item->diff2, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </form>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('form-bases-mes');
    if (!form) return;
    var inputs = form.querySelectorAll('input.base-2025-input');
    function isEmpty(input) {
        var v = (input.value || '').trim();
        return v === '' || parseFloat(v) === 0;
    }
    function nextEmptyFrom(current) {
        var list = Array.prototype.slice.call(inputs);
        var i = list.indexOf(current);
        for (var j = i + 1; j < list.length; j++) {
            if (isEmpty(list[j])) return list[j];
        }
        for (var k = 0; k < i; k++) {
            if (isEmpty(list[k])) return list[k];
        }
        return null;
    }
    inputs.forEach(function(input) {
        input.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            var next = nextEmptyFrom(input);
            if (next) {
                next.focus();
                next.select();
            }
        });
    });
})();
</script>
@endsection
