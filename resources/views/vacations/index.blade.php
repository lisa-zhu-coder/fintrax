@extends('layouts.app')

@section('title', 'Vacaciones')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <h1 class="text-lg font-semibold">Vacaciones</h1>
            <p class="text-sm text-slate-500">Gestión de vacaciones por tienda y año.</p>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('vacations.index') }}" class="flex flex-wrap items-end gap-4 mb-4">
            <label class="block min-w-[120px]">
                <span class="text-xs font-semibold text-slate-700">Año *</span>
                <select name="year" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" required>
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Filtrar</button>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Tienda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($stores as $s)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('vacations.store', ['store' => $s, 'year' => $year]) }}" class="font-semibold text-slate-900 hover:text-brand-600">{{ $s->name }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
