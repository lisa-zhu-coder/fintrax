@extends('layouts.app')

@section('title', 'Añadir horas — ' . $store->name . ' — ' . $monthName . ' ' . $year)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('overtime.index') }}" class="hover:text-brand-600">Horas extras</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('overtime.store-months', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $store->name }}</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('overtime.month', ['store' => $store, 'year' => $year, 'month' => $month]) }}" class="hover:text-brand-600">{{ $monthName }} {{ $year }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">Añadir horas</span>
                </nav>
                <h1 class="text-lg font-semibold">Añadir horas</h1>
                <p class="text-sm text-slate-500">{{ $store->name }} — {{ $monthName }} {{ $year }}</p>
            </div>
            <a href="{{ route('overtime.month', ['store' => $store, 'year' => $year, 'month' => $month]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 max-w-md">
        <form method="POST" action="{{ route('overtime.store', ['store' => $store, 'year' => $year, 'month' => $month]) }}" class="space-y-4">
            @csrf
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Empleada</span>
                <select name="employee_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">— Seleccionar —</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
                @error('employee_id')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Fecha</span>
                <input type="date" name="date" value="{{ old('date', \Carbon\Carbon::createFromDate($year, $month, 1)->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                @error('date')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Horas extras</span>
                <input type="number" name="overtime_hours" value="{{ old('overtime_hours', 0) }}" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Horas domingo/festivos</span>
                <input type="number" name="sunday_holiday_hours" value="{{ old('sunday_holiday_hours', 0) }}" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
        </form>
    </div>
</div>
@endsection
