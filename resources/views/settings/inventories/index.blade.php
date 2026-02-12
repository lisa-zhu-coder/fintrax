@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <h1 class="text-lg font-semibold">Configuración de inventario</h1>
            <p class="text-sm text-slate-500">Activar o desactivar el inventario de anillos para esta empresa (solo Super Admin).</p>
        </div>
    </header>

    @if(auth()->user()->isSuperAdmin())
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold">Inventario de anillos</h2>
                <p class="text-sm text-slate-500">Activar o desactivar el inventario de anillos para esta empresa.</p>
            </div>
            <form method="POST" action="{{ route('inventory-settings.toggle-rings') }}">
                @csrf
                <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold {{ $ringsEnabled ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-brand-600 text-white hover:bg-brand-700' }}">
                    {{ $ringsEnabled ? 'Desactivar' : 'Activar' }} inventario de anillos
                </button>
            </form>
        </div>
    </div>
    @else
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <p class="text-sm text-slate-600">Solo el Super Admin puede modificar la configuración del inventario de anillos.</p>
    </div>
    @endif
</div>
@endsection
