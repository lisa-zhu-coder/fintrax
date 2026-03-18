@extends('layouts.app')

@section('title', 'Puestos de empleado')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <nav class="text-xs text-slate-500 mb-1">
                <span class="text-slate-700">Ajustes</span>
                <span class="mx-1">/</span>
                <span>Puestos de empleado</span>
            </nav>
            <h1 class="text-lg font-semibold">Puestos de empleado</h1>
            <p class="text-sm text-slate-500">Define los puestos que podrán elegirse al crear o editar fichas de empleados (dependiente, encargado, etc.).</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('job-positions-settings.store') }}" class="mb-6 flex flex-wrap items-end gap-3">
            @csrf
            <label class="min-w-[200px]">
                <span class="block text-xs font-semibold text-slate-700 mb-1">Nuevo puesto</span>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Dependiente, Encargado…"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Añadir puesto</button>
        </form>

        <h2 class="text-sm font-semibold text-slate-800 mb-3">Listado de puestos</h2>
        @if($jobPositions->isEmpty())
            <p class="text-slate-600">No hay puestos definidos. Añade al menos uno para poder asignarlo al crear empleados.</p>
        @else
            <ul class="space-y-2">
                @foreach($jobPositions as $jp)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3">
                    <form method="POST" action="{{ route('job-positions-settings.update', $jp) }}" class="flex flex-1 min-w-0 items-center gap-3">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ old('name.' . $jp->id, $jp->name) }}" required maxlength="255"
                            class="flex-1 min-w-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        <button type="submit" class="shrink-0 rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-300">Guardar</button>
                    </form>
                    <form method="POST" action="{{ route('job-positions-settings.destroy', $jp) }}" class="inline" onsubmit="return confirm('¿Eliminar este puesto? Solo si ningún empleado lo usa.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50 text-sm font-semibold">Eliminar</button>
                    </form>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
