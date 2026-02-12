@extends('layouts.app')

@section('title', 'Importar CSV — Objetivos mensuales')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('objectives.index') }}" class="hover:text-brand-600">Objetivos mensuales</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">Importar CSV</span>
                </nav>
                <h1 class="text-lg font-semibold">Importar bases 2025 desde CSV</h1>
                <p class="text-sm text-slate-500">Elige la tienda, descarga la plantilla y sube el archivo con las fechas y importes.</p>
            </div>
            <a href="{{ route('objectives.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 space-y-6">
        <section>
            <h2 class="text-sm font-semibold text-slate-700 mb-2">1. Descargar plantilla</h2>
            <p class="text-sm text-slate-500 mb-3">La plantilla incluye las columnas: <strong>Fecha 2025 (dd/mm/yyyy)</strong> y <strong>Base 2025</strong>. Rellena las fechas y los importes y guarda como CSV (separador punto y coma o coma).</p>
            <a href="{{ route('objectives.import.template') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Descargar plantilla CSV
            </a>
        </section>

        <section>
            <h2 class="text-sm font-semibold text-slate-700 mb-2">2. Elegir tienda e importar</h2>
            <form method="POST" action="{{ route('objectives.import.process') }}" enctype="multipart/form-data" class="space-y-4 max-w-md">
                @csrf
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda</span>
                    <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">— Seleccionar tienda —</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ old('store_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('store_id')
                        <span class="text-xs text-rose-600">{{ $message }}</span>
                    @enderror
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Archivo CSV</span>
                    <input type="file" name="file" accept=".csv,.txt" required class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100"/>
                    @error('file')
                        <span class="text-xs text-rose-600">{{ $message }}</span>
                    @enderror
                </label>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Importar CSV</button>
            </form>
        </section>
    </div>
</div>
@endsection
