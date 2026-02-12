@extends('layouts.app')

@section('title', 'Vacaciones — ' . $store->name . ' — ' . $year)

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('vacations.index') }}" class="hover:text-brand-600">Vacaciones</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $store->name }} — {{ $year }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $store->name }} — {{ $year }}</h1>
                <p class="text-sm text-slate-500">Resumen anual de vacaciones por empleado.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('vacations.calendar-months', ['store' => $store, 'year' => $year]) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Ver calendario</a>
                <a href="{{ route('vacations.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Tiendas</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('vacations.update-periods', ['store' => $store, 'year' => $year]) }}" id="periods-form">
            @csrf
            @method('PUT')
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Empleado</th>
                            <th class="px-3 py-2 text-left">Fecha inicio</th>
                            <th class="px-3 py-2 text-left">Fecha fin</th>
                            <th class="px-3 py-2 text-right">Días trabajados</th>
                            <th class="px-3 py-2 text-right">Vacaciones generadas</th>
                            <th class="px-3 py-2 text-right">Vacaciones disfrutadas</th>
                            <th class="px-3 py-2 text-right">Vacaciones restantes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($employeesData as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-medium">{{ $row->employee->full_name }}</td>
                                <td class="px-3 py-2">
                                    @if(auth()->user()->hasPermission('hr.vacations.edit'))
                                    <input type="date" name="periods[{{ $row->employee->id }}][period_start]" value="{{ $row->period_start }}" class="w-36 rounded border border-slate-200 px-2 py-1 text-sm"/>
                                    @else
                                    {{ $row->period_start ? \Carbon\Carbon::parse($row->period_start)->format('d/m/Y') : '—' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if(auth()->user()->hasPermission('hr.vacations.edit'))
                                    <input type="date" name="periods[{{ $row->employee->id }}][period_end]" value="{{ $row->period_end }}" class="w-36 rounded border border-slate-200 px-2 py-1 text-sm" placeholder="Opcional"/>
                                    @else
                                    {{ $row->period_end ? \Carbon\Carbon::parse($row->period_end)->format('d/m/Y') : '—' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">{{ $row->days_worked }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($row->vacation_generated, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right">{{ $row->vacation_taken }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ number_format($row->vacation_remaining, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        @if(empty($employeesData))
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-slate-500">No hay empleados en esta tienda.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @if(auth()->user()->hasPermission('hr.vacations.edit') && !empty($employeesData))
            <div class="mt-4">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar periodos</button>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection
