@extends('layouts.app')

@section('title', 'Objetivos de ventas')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <h1 class="text-lg font-semibold">Objetivos de ventas</h1>
            <p class="text-sm text-slate-500">Gestiona los objetivos (Objetivo 1, Objetivo 2, etc.) y asigna porcentajes por mes. Los cambios se reflejan en el módulo Objetivos mensuales.</p>
        </div>
    </header>

    {{-- Gestionar objetivos (añadir / eliminar) --}}
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">Objetivos definidos</h2>
        <p class="text-xs text-slate-500 mb-4">Añade o elimina objetivos. Luego configura los porcentajes por mes debajo.</p>
        <div class="flex flex-wrap items-center gap-3 mb-4">
            @foreach($definitions as $def)
                <span class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700">
                    {{ $def->name }}
                    <form method="POST" action="{{ route('objectives-settings.definitions.destroy', $def) }}" class="inline" onsubmit="return confirm('¿Eliminar este objetivo? Se borrarán sus porcentajes guardados.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded p-0.5 text-slate-500 hover:bg-rose-100 hover:text-rose-600" title="Eliminar">×</button>
                    </form>
                </span>
            @endforeach
        </div>
        <form method="POST" action="{{ route('objectives-settings.definitions.store') }}" class="flex flex-wrap items-center gap-2">
            @csrf
            <input type="text" name="name" placeholder="Nombre (ej. Objetivo 3)" maxlength="100" required class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" />
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Añadir objetivo</button>
        </form>
    </div>

    @if($definitions->isEmpty())
        <p class="text-sm text-slate-600">Añade al menos un objetivo arriba para configurar los porcentajes por mes.</p>
    @else
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('objectives-settings.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
            <label class="block min-w-[120px]">
                <span class="text-xs font-semibold text-slate-700">Año</span>
                <select name="year" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block min-w-[200px]">
                <span class="text-xs font-semibold text-slate-700">Configuración para</span>
                <select name="store_id" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Todas las tiendas (general)</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ (string)$storeId === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </label>
            <input type="hidden" name="year" value="{{ $year }}" form="objectives-get-form"/>
        </form>
        <form id="objectives-get-form" method="GET" action="{{ route('objectives-settings.index') }}" class="hidden" aria-hidden="true">
            <input type="hidden" name="year" value="{{ $year }}"/>
            <input type="hidden" name="store_id" value="{{ $storeId ?? '' }}"/>
        </form>

        <form method="POST" action="{{ route('objectives-settings.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}"/>
            @if($storeId !== null)
                <input type="hidden" name="store_id" value="{{ $storeId }}"/>
            @else
                <input type="hidden" name="store_id" value=""/>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Mes</th>
                            @foreach($definitions as $def)
                                <th class="px-3 py-2 text-right w-40">% {{ $def->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($months as $monthKey => $monthName)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-medium">{{ $monthName }}</td>
                                @foreach($definitions as $def)
                                    <td class="px-3 py-2">
                                        <input type="number" name="months[{{ $monthKey }}][objectives][{{ $def->id }}]" value="{{ old("months.{$monthKey}.objectives.{$def->id}", $settingsByMonth[$monthKey][$def->id] ?? 0) }}" step="0.01" min="0" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-right text-sm outline-none ring-brand-200 focus:ring-4"/>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-slate-500">Los porcentajes deben ser 0 o positivos.</p>

            <div class="flex items-center justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar cambios</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
