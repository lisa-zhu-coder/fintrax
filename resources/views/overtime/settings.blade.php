@extends('layouts.app')

@section('title', 'Ajustes de horas extras')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <span class="text-slate-500">Ajustes</span>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">Ajustes de horas extras</span>
                </nav>
                <h1 class="text-lg font-semibold">Ajustes de horas extras</h1>
                <p class="text-sm text-slate-500">Precio por hora extra y por hora domingo/festivo para cada empleada. Si no hay precio configurado se usa 0.</p>
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <form method="POST" action="{{ route('overtime-settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Empleada</th>
                        <th class="px-3 py-2 text-right">Precio hora extra (€)</th>
                        <th class="px-3 py-2 text-right">Precio hora domingo/festivo (€)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($employees as $emp)
                        @php $s = $settings->get($emp->id); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $emp->full_name }}</td>
                            <td class="px-3 py-2">
                                <input type="number" name="employee_{{ $emp->id }}_overtime" value="{{ old('employee_' . $emp->id . '_overtime', $s ? $s->price_overtime_hour : 0) }}" step="0.01" min="0" class="w-24 rounded-lg border border-slate-200 px-2 py-1 text-right text-sm"/>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" name="employee_{{ $emp->id }}_sunday" value="{{ old('employee_' . $emp->id . '_sunday', $s ? $s->price_sunday_holiday_hour : 0) }}" step="0.01" min="0" class="w-24 rounded-lg border border-slate-200 px-2 py-1 text-right text-sm"/>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($employees->isEmpty())
                <p class="text-slate-500 py-4">No hay empleadas. Añade empleadas en el módulo Empleados.</p>
            @else
                <button type="submit" class="mt-4 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar precios</button>
            @endif
        </form>
    </div>
</div>
@endsection
