@extends('layouts.app')

@section('title', 'Crear Rol')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Crear rol</h1>
                <p class="text-sm text-slate-500">Define un nuevo rol y sus permisos por módulo</p>
            </div>
            <a href="{{ route('roles.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('roles.store') }}" id="role-form" class="space-y-6">
            @csrf

            @if($errors->any())
                <div class="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 ring-1 ring-rose-100">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Clave (identificador único)</span>
                    <input type="text" name="key" value="{{ old('key') }}" required
                        placeholder="ej: gestor_tienda"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    <p class="mt-1 text-xs text-slate-500">Solo letras minúsculas, números y guiones bajos</p>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nivel</span>
                    <input type="number" name="level" value="{{ old('level', 5) }}" min="0" max="99"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    <p class="mt-1 text-xs text-slate-500">0 = mayor poder. Usado para ordenar roles.</p>
                </label>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre</span>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block md:col-span-1">
                    <span class="text-xs font-semibold text-slate-700">Descripción</span>
                    <input type="text" name="description" value="{{ old('description') }}"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>

            @php
                $currentPermissions = old('permissions', []);
            @endphp

            <div class="border-t border-slate-200 pt-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold">Permisos por módulo</h2>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-action="select-all" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Seleccionar todo
                        </button>
                        <button type="button" data-action="read-only" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Solo lectura
                        </button>
                        <button type="button" data-action="full-access" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Acceso completo
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach ($permissionModules as $module)
                    <details class="group rounded-xl border border-slate-200 bg-slate-50/50" data-module="{{ $module['key'] }}">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100/80 [&::-webkit-details-marker]:hidden">
                            <span>{{ $module['label'] }}</span>
                            <span class="flex gap-2">
                                <button type="button" class="module-btn rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-white hover:shadow" data-module="{{ $module['key'] }}" data-set="all">Todo</button>
                                <button type="button" class="module-btn rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-white hover:shadow" data-module="{{ $module['key'] }}" data-set="view">Solo ver</button>
                                <button type="button" class="module-btn rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-white hover:shadow" data-module="{{ $module['key'] }}" data-set="none">Ninguno</button>
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div class="border-t border-slate-200 bg-white px-4 py-3">
                            @foreach ($module['items'] as $item)
                            <div class="mb-4 last:mb-0">
                                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</div>
                                <div class="flex flex-wrap gap-x-6 gap-y-2">
                                    @foreach ($item['permissions'] as $perm)
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input type="checkbox"
                                            name="permissions[{{ $perm['key'] }}]"
                                            value="1"
                                            class="perm-cb rounded border-slate-300 text-brand-600 focus:ring-brand-200"
                                            data-module="{{ $module['key'] }}"
                                            data-action="{{ $perm['action'] }}"
                                            {{ !empty($currentPermissions[$perm['key']]) ? 'checked' : '' }}/>
                                        <span class="text-sm text-slate-700">{{ $perm['label'] }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </details>
                    @endforeach
                </div>
            </div>
        </form>

        <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
            <a href="{{ route('roles.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit" form="role-form" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Crear rol
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('role-form');
    if (!form) return;

    const allCbs = form.querySelectorAll('.perm-cb');
    const byModule = {};
    allCbs.forEach(cb => {
        const m = cb.dataset.module;
        if (!byModule[m]) byModule[m] = [];
        byModule[m].push(cb);
    });

    function setAll(checked) {
        allCbs.forEach(cb => { cb.checked = checked; });
    }

    function setReadOnly() {
        allCbs.forEach(cb => {
            cb.checked = cb.dataset.action === 'view';
        });
    }

    function setFullAccess() {
        allCbs.forEach(cb => { cb.checked = true; });
    }

    function setModule(moduleKey, set) {
        const cbs = byModule[moduleKey] || [];
        if (set === 'all') cbs.forEach(cb => { cb.checked = true; });
        else if (set === 'view') cbs.forEach(cb => { cb.checked = cb.dataset.action === 'view'; });
        else if (set === 'none') cbs.forEach(cb => { cb.checked = false; });
    }

    form.querySelectorAll('[data-action="select-all"]').forEach(btn => {
        btn.addEventListener('click', () => setAll(true));
    });
    form.querySelectorAll('[data-action="read-only"]').forEach(btn => {
        btn.addEventListener('click', setReadOnly);
    });
    form.querySelectorAll('[data-action="full-access"]').forEach(btn => {
        btn.addEventListener('click', setFullAccess);
    });

    form.querySelectorAll('.module-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            setModule(btn.dataset.module, btn.dataset.set);
        });
    });
})();
</script>
@endsection
