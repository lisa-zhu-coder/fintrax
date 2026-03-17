@extends('layouts.app')

@section('title', 'Nueva plantilla de email')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <nav class="mb-1 text-xs text-slate-500"><a href="{{ route('email-templates-settings.index') }}" class="text-brand-600 hover:underline">Plantillas de email RRHH</a><span class="mx-1">/</span><span>Nueva</span></nav>
        <h1 class="text-lg font-semibold">Nueva plantilla</h1>
    </header>

    <form method="POST" action="{{ route('email-templates-settings.store') }}" class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 space-y-4">
        @csrf
        <label class="block">
            <span class="text-xs font-semibold text-slate-700">Nombre</span>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Nómina mensual" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"/>
        </label>
        <label class="block">
            <span class="text-xs font-semibold text-slate-700">Tipo</span>
            <select name="type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                <option value="payroll" {{ old('type', 'payroll') === 'payroll' ? 'selected' : '' }}>Nómina</option>
                <option value="document" {{ old('type') === 'document' ? 'selected' : '' }}>Documento</option>
            </select>
        </label>
        <label class="block">
            <span class="text-xs font-semibold text-slate-700">Asunto</span>
            <input type="text" name="subject" value="{{ old('subject', 'Nómina {{mes}} - {{empresa}}') }}" required maxlength="500" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"/>
        </label>
        <label class="block">
            <span class="text-xs font-semibold text-slate-700">Cuerpo (variables: &#123;&#123;nombre&#125;&#125;, &#123;&#123;mes&#125;&#125;, &#123;&#123;empresa&#125;&#125;)</span>
            <textarea name="body" rows="6" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">{{ old('body', "Hola {{nombre}},\n\nAdjuntamos tu nómina correspondiente al mes de {{mes}}.\n\nUn saludo,\n{{empresa}}") }}</textarea>
        </label>
        <div class="flex gap-2 pt-2">
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Crear plantilla</button>
            <a href="{{ route('email-templates-settings.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
</div>
@endsection
