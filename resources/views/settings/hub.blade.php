@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        @include('partials.settings-section-tabs', ['group' => $group])
        <div>
            <nav class="text-xs text-slate-500 mb-1">
                <span>Ajustes</span>
                <span class="mx-1">/</span>
                <span class="text-slate-700">{{ $title }}</span>
            </nav>
            <h1 class="text-lg font-semibold">{{ $title }}</h1>
            <p class="text-sm text-slate-500">No hay opciones de configuración disponibles en esta sección por el momento.</p>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-8 shadow-soft ring-1 ring-slate-100 text-center text-slate-500">
        <p class="text-sm">Esta sección está preparada para futuros ajustes del módulo.</p>
    </div>
</div>
@endsection
