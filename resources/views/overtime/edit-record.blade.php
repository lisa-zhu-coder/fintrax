@extends('layouts.app')

@section('title', 'Editar registro — Horas extras')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('overtime.index') }}" class="hover:text-brand-600">Horas extras</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('overtime.employee', $employee) }}" class="hover:text-brand-600">{{ $employee->full_name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">Editar registro</span>
                </nav>
                <h1 class="text-lg font-semibold">Editar registro</h1>
                <p class="text-sm text-slate-500">{{ $employee->full_name }} — {{ $overtimeRecord->date->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('overtime.employee', $employee) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 max-w-md">
        <form method="POST" action="{{ route('overtime.records.update', $overtimeRecord) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Fecha</span>
                <input type="date" name="date" value="{{ old('date', $overtimeRecord->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                @error('date')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Horas extras</span>
                <input type="number" name="overtime_hours" value="{{ old('overtime_hours', $overtimeRecord->overtime_hours) }}" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Horas domingo/festivos</span>
                <input type="number" name="sunday_holiday_hours" value="{{ old('sunday_holiday_hours', $overtimeRecord->sunday_holiday_hours) }}" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
        </form>
    </div>
</div>
@endsection
