@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Empleados</h1>
                <p class="text-sm text-slate-500">Gestiona la información de todos los empleados</p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->hasPermission('hr.employees.configure'))
                <form method="POST" action="{{ route('employees.payrolls.upload') }}" enctype="multipart/form-data" class="inline">
                    @csrf
                    <input type="file" name="payroll" id="payrollFileInputAuto" accept=".pdf" class="hidden" onchange="this.form.submit()"/>
                    <button type="button" onclick="document.getElementById('payrollFileInputAuto').click()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Subir nómina
                    </button>
                </form>
                @endif
                @if(auth()->user()->hasPermission('hr.employees.create'))
                <a href="{{ route('employees.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir empleado
                </a>
                @endif
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">DNI</th>
                        <th class="px-3 py-2">Puesto</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2">Usuario</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ $employee->full_name }}</td>
                            <td class="px-3 py-2">{{ $employee->dni ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $employee->position }}</td>
                            <td class="px-3 py-2">
                                @if($employee->stores->count() > 0)
                                    @if($employee->stores->count() == $totalStores && $totalStores > 0)
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
                                            Todas las tiendas
                                        </span>
                                    @else
                                        {{ $employee->stores->pluck('name')->join(', ') }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($employee->user)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
                                        {{ $employee->user->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Sin usuario</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('employees.show', $employee) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Ver
                                    </a>
                                    @if(auth()->user()->hasPermission('hr.employees.edit'))
                                    <a href="{{ route('employees.edit', $employee) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                        Editar
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('hr.employees.delete'))
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('¿Estás seguro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                            Eliminar
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">No hay empleados registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
