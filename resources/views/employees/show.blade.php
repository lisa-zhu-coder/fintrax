@extends('layouts.app')

@section('title', 'Detalle de Empleado')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Detalle de Empleado</h1>
                <p class="text-sm text-slate-500">Información completa del empleado</p>
            </div>
            <div class="flex items-center gap-2">
                @if($employee->trashed() && (auth()->user()->hasPermission('hr.employees.edit') || auth()->user()->hasPermission('hr.employees.delete')))
                <form method="POST" action="{{ route('employees.restore', $employee->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                        Restaurar empleado
                    </button>
                </form>
                @endif
                @if(auth()->user()->hasPermission('hr.employees.configure'))
                <a href="{{ route('employees.edit', $employee) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Editar
                </a>
                @endif
                <a href="{{ route('employees.index', $employee->trashed() ? ['archived' => 1] : []) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    ← Volver
                </a>
            </div>
        </div>
    </header>

    @if($employee->trashed())
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
        <strong>Empleado archivado.</strong> Ya no está en la empresa. Puedes restaurarlo desde la lista de empleados → Archivados.
    </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="space-y-6">
            <!-- Información Personal -->
            <div class="rounded-xl border-2 border-blue-100 bg-blue-50/30 p-4 ring-1 ring-blue-100">
                <h3 class="mb-4 text-sm font-semibold text-blue-900">Información Personal</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Nombre completo</span>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->full_name }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500">DNI</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->dni ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Teléfono</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->phone ?? '—' }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Correo electrónico</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->email ?? '—' }}</div>
                    </div>
                    @if($employee->street || $employee->postal_code || $employee->city)
                    <div class="md:col-span-2">
                        <span class="text-xs font-semibold text-slate-500">Dirección</span>
                        <div class="mt-1 text-sm text-slate-900">
                            {{ $employee->street }}{{ $employee->postal_code ? ', ' . $employee->postal_code : '' }}{{ $employee->city ? ', ' . $employee->city : '' }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Cuenta de Usuario -->
            @if($employee->user)
            <div class="rounded-xl border-2 border-brand-100 bg-brand-50/30 p-4 ring-1 ring-brand-100">
                <h3 class="mb-4 text-sm font-semibold text-brand-900">Cuenta de Usuario</h3>
                <div>
                    <span class="text-xs font-semibold text-slate-500">Usuario asociado</span>
                    <div class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
                            {{ $employee->user->name }} ({{ $employee->user->role->name }})
                        </span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Información Laboral -->
            <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
                <h3 class="mb-4 text-sm font-semibold text-emerald-900">Información Laboral</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Puesto</span>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->position }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Horas contratadas</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->hours }} horas</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Fecha de inicio</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->start_date->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Fecha de finalización</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->end_date ? $employee->end_date->format('d/m/Y') : '—' }}</div>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-xs font-semibold text-slate-500">Tiendas</span>
                        <div class="mt-1">
                            @if($employee->stores->count() > 0)
                                @if($employee->stores->count() == $totalStores && $totalStores > 0)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
                                        Todas las tiendas
                                    </span>
                                @else
                                    @foreach($employee->stores as $store)
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700 mr-2">
                                            {{ $store->name }}
                                        </span>
                                    @endforeach
                                @endif
                            @else
                                <span class="text-sm text-slate-500">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Financiera -->
            @php
                $canViewSalary = auth()->user()->hasPermission('hr.employees.view_salary_store') || (auth()->user()->hasPermission('hr.employees.view_salary_own') && $employee->user_id === auth()->id());
                $hasFinancialInfo = $employee->social_security || $employee->iban || ($canViewSalary && ($employee->gross_salary || $employee->net_salary));
            @endphp
            @if($hasFinancialInfo)
            <div class="rounded-xl border-2 border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-100">
                <h3 class="mb-4 text-sm font-semibold text-amber-900">Información Financiera</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @if($employee->social_security)
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Nº Seguridad Social</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->social_security }}</div>
                    </div>
                    @endif
                    @if($employee->iban)
                    <div>
                        <span class="text-xs font-semibold text-slate-500">IBAN</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->iban }}</div>
                    </div>
                    @endif
                    @if($canViewSalary && $employee->gross_salary)
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Salario bruto mensual</span>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($employee->gross_salary, 2, ',', '.') }} €</div>
                    </div>
                    @endif
                    @if($canViewSalary && $employee->net_salary)
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Salario neto mensual</span>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($employee->net_salary, 2, ',', '.') }} €</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Información de Uniforme -->
            @if($employee->shirt_size || $employee->blazer_size || $employee->pants_size)
            <div class="rounded-xl border-2 border-purple-100 bg-purple-50/30 p-4 ring-1 ring-purple-100">
                <h3 class="mb-4 text-sm font-semibold text-purple-900">Información de Uniforme</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @if($employee->shirt_size)
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Talla de camiseta</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->shirt_size }}</div>
                    </div>
                    @endif
                    @if($employee->blazer_size)
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Talla de blazer</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->blazer_size }}</div>
                    </div>
                    @endif
                    @if($employee->pants_size)
                    <div>
                        <span class="text-xs font-semibold text-slate-500">Talla de pantalones</span>
                        <div class="mt-1 text-sm text-slate-900">{{ $employee->pants_size }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if(auth()->user()->hasPermission('rrhh.documents.view'))
            <!-- Documentos -->
            <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-4 ring-1 ring-slate-200">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Documentos</h3>
                    @if(auth()->user()->hasPermission('rrhh.documents.create'))
                    <button type="button" onclick="document.getElementById('modalDocument').classList.remove('hidden')" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Subir documento
                    </button>
                    @endif
                </div>
                <div class="space-y-2">
                    @forelse($employee->documents as $doc)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-3 ring-1 ring-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-lg bg-slate-100 text-slate-600">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ $doc->title }}</div>
                                <div class="text-xs text-slate-500">{{ $doc->document_date->format('d/m/Y') }} · {{ \App\Models\EmployeeDocument::TYPES[$doc->type] ?? $doc->type }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('employees.documents.download', [$employee, $doc]) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">Descargar</a>
                            @if(auth()->user()->hasPermission('rrhh.documents.delete'))
                            <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $doc]) }}" class="inline" onsubmit="return confirm('¿Eliminar este documento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center text-xs text-slate-500">No hay documentos.</div>
                    @endforelse
                </div>
            </div>
            @endif

            <!-- Nóminas -->
            <div class="rounded-xl border-2 border-indigo-100 bg-indigo-50/30 p-4 ring-1 ring-indigo-100">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-indigo-900">Nóminas</h3>
                    @if(auth()->user()->hasPermission('hr.employees.configure'))
                    <form method="POST" action="{{ route('employees.payrolls', $employee) }}" enctype="multipart/form-data" class="inline">
                        @csrf
                        <input type="file" name="payrolls[]" id="payrollFileInput" accept=".pdf" multiple class="hidden" onchange="this.form.submit()"/>
                        <button type="button" onclick="document.getElementById('payrollFileInput').click()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Subir nóminas (PDF)
                        </button>
                    </form>
                    @endif
                </div>
                <div class="space-y-2">
                    @forelse($employee->payrolls as $payroll)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-3 ring-1 ring-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-100 text-indigo-700">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $payroll->file_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $payroll->date ? $payroll->date->format('d/m/Y') : '—' }} @if($payroll->sent_at)<span class="text-emerald-600">· Enviado</span>@else<span class="text-amber-600">· Pendiente</span>@endif</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('payrolls.view', $payroll) }}" target="_blank" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">Ver</a>
                                @if(!$payroll->sent_at && auth()->user()->hasPermission('payroll.send'))
                                <a href="{{ route('payroll.send', $payroll) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 ring-1 ring-transparent hover:ring-emerald-100">Enviar</a>
                                @endif
                                @if(auth()->user()->hasPermission('payroll.delete'))
                                <form method="POST" action="{{ route('payroll.destroy', $payroll) }}" class="inline" onsubmit="return confirm('¿Eliminar esta nómina?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center text-xs text-slate-500">
                            No hay nóminas asociadas.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->hasPermission('rrhh.documents.create'))
<div id="modalDocument" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50" onclick="document.getElementById('modalDocument').classList.add('hidden')"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Subir documento</h3>
            <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo de documento</span>
                    <select name="type" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                        @foreach(\App\Models\EmployeeDocument::TYPES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre o título</span>
                    <input type="text" name="title" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Ej. Contrato indefinido">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha</span>
                    <input type="date" name="document_date" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Archivo (PDF)</span>
                    <input type="file" name="file" accept=".pdf" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modalDocument').classList.add('hidden')" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Subir</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
