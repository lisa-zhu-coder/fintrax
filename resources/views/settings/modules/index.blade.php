@extends('layouts.app')

@section('title', 'Módulos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <h1 class="text-lg font-semibold">Módulos</h1>
            <p class="text-sm text-slate-500">Activa o desactiva módulos para la empresa {{ $company->name }}.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('module-settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-4">
                <div>
                    <p class="font-semibold text-slate-800">Activar módulo Clientes</p>
                    <p class="text-sm text-slate-500">Muestra el menú Clientes (Pedidos clientes y Reparaciones) en el lateral para los usuarios con permiso.</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="hidden" name="clients_module_enabled" value="0">
                    <input type="checkbox" name="clients_module_enabled" value="1" {{ old('clients_module_enabled', $company->clients_module_enabled) ? 'checked' : '' }}
                        class="peer sr-only">
                    <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-brand-200"></div>
                    <span class="ml-3 text-sm font-medium text-slate-700">{{ $company->clients_module_enabled ? 'Activado' : 'Desactivado' }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
