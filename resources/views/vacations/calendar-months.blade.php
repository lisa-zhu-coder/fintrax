@extends('layouts.app')

@section('title', 'Calendario — ' . $store->name . ' — ' . $year)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('vacations.index') }}" class="hover:text-brand-600">Vacaciones</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('vacations.store', ['store' => $store, 'year' => $year]) }}" class="hover:text-brand-600">{{ $store->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $year }} — Calendario</span>
                </nav>
                <h1 class="text-lg font-semibold">Calendario {{ $year }}</h1>
                <p class="text-sm text-slate-500">Selecciona un mes para ver y registrar vacaciones.</p>
            </div>
            <a href="{{ route('vacations.store', ['store' => $store, 'year' => $year]) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Resumen tienda</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($months as $m)
                <a href="{{ route('vacations.calendar-month', ['store' => $store, 'year' => $year, 'month' => $m->month]) }}" class="rounded-xl border border-slate-200 p-4 text-center hover:bg-slate-50 hover:border-brand-200 transition-colors">
                    <span class="font-semibold text-slate-900">{{ $m->monthName }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
