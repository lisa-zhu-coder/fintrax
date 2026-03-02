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
                <p class="text-sm text-slate-500">Define los tipos de horas extras de tu empresa y el precio por hora de cada tipo para cada empleada.</p>
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Tipos de horas extras --}}
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Tipos de horas extras</h2>
        @if(auth()->user()->hasPermission('settings.overtime.edit'))
        <form method="POST" action="{{ route('overtime-types.store') }}" class="mb-6 flex flex-wrap items-end gap-3">
            @csrf
            <label class="min-w-[200px]">
                <span class="block text-xs font-semibold text-slate-700 mb-1">Nuevo tipo</span>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Horas extras, Domingo/festivos..."
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Añadir tipo</button>
        </form>
        @endif

        @if($types->isEmpty())
            <p class="text-slate-600">No hay tipos de horas extras. Añade al menos uno para poder configurar precios en el cuadro.</p>
        @else
            <ul class="space-y-2">
                @foreach($types as $type)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
                    @if(auth()->user()->hasPermission('settings.overtime.edit'))
                    <form method="POST" action="{{ route('overtime-types.update', $type) }}" class="flex flex-1 min-w-0 items-center gap-3">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ old('name.' . $type->id, $type->name) }}" required maxlength="255"
                            class="flex-1 min-w-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        <button type="submit" class="shrink-0 rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-300">Guardar</button>
                    </form>
                    @else
                    <span class="font-medium text-slate-800">{{ $type->name }}</span>
                    @endif
                    @if(auth()->user()->hasPermission('settings.overtime.edit'))
                    <form method="POST" action="{{ route('overtime-types.destroy', $type) }}" class="inline" onsubmit="return confirm('¿Eliminar este tipo? Los registros existentes perderán la referencia.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" title="Eliminar">Eliminar</button>
                    </form>
                    @endif
                </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Cuadro de precios por empleada y tipo --}}
    @if($types->isNotEmpty())
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 overflow-x-auto">
        <form method="POST" action="{{ route('overtime-settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <h2 class="text-sm font-semibold text-slate-800 mb-3">Precio por hora (€) por empleada y tipo</h2>
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Empleada</th>
                        @foreach($types as $type)
                        <th class="px-3 py-2 text-right">{{ $type->name }} (€)</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($employees as $emp)
                        @php
                            $empSettings = $settings->get($emp->id) ?? collect();
                            $empSettingsByType = $empSettings->keyBy('overtime_type_id');
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $emp->full_name }}</td>
                            @foreach($types as $type)
                            <td class="px-3 py-2">
                                @php $s = $empSettingsByType->get($type->id); @endphp
                                <input type="number" name="employee_{{ $emp->id }}_type_{{ $type->id }}" value="{{ old('employee_' . $emp->id . '_type_' . $type->id, $s ? $s->price_per_hour : 0) }}" step="0.01" min="0" class="w-24 rounded-lg border border-slate-200 px-2 py-1 text-right text-sm"/>
                            </td>
                            @endforeach
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
    @endif
</div>
@endsection
