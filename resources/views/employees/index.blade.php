@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-lg font-semibold">Empleados</h1>
                <p class="text-sm text-slate-500">Gestiona la información de todos los empleados</p>
                <div class="mt-2 flex gap-1">
                    <a href="{{ route('employees.index', array_filter(['position' => request('position'), 'store_id' => request('store_id')])) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ !($showArchived ?? false) ? 'bg-brand-100 text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}">Activos</a>
                    <a href="{{ route('employees.index', array_merge(['archived' => 1], array_filter(['position' => request('position'), 'store_id' => request('store_id')]))) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $showArchived ?? false ? 'bg-slate-200 text-slate-800' : 'text-slate-600 hover:bg-slate-100' }}">Archivados</a>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()->hasPermission('hr.employees.edit') && !($showArchived ?? false))
                    @if($canReorder ?? false)
                    <button type="button" id="btnToggleReorder" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 6h2v2H8V6zm0 5h2v2H8v-2zm0 5h2v2H8v-2zm4-10h8v2h-8V6zm0 5h8v2h-8v-2zm0 5h8v2h-8v-2z"/></svg>
                        <span id="btnToggleReorderLabel">Ordenar empleados</span>
                    </button>
                    @elseif($hasFilters ?? false)
                    <span class="text-xs text-slate-500 max-w-xs" title="Los filtros ocultan parte de la lista">Quita los filtros de puesto o tienda para cambiar el orden de la lista.</span>
                    @endif
                @endif
                @if(auth()->user()->hasPermission('hr.employees.configure'))
                <form method="POST" action="{{ route('employees.payrolls.upload') }}" enctype="multipart/form-data" class="inline">
                    @csrf
                    <input type="file" name="payroll" id="payrollFileInputAuto" accept=".pdf" class="hidden" onchange="this.form.submit()"/>
                    <button type="button" onclick="document.getElementById('payrollFileInputAuto').click()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50" title="Puedes subir un PDF con una o varias nóminas; cada página se asignará al empleado por nombre, DNI o número de la seguridad social. PDFs con muchas páginas pueden tardar 1–2 minutos; no cierres la pestaña.">
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

    @if($positions->isNotEmpty() || $storesForFilter->isNotEmpty())
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap items-end gap-3">
            @if(request('archived'))
            <input type="hidden" name="archived" value="1">
            @endif
            <label class="block">
                <span class="text-xs font-semibold text-slate-600">Puesto</span>
                <select name="position" class="mt-1 block w-full min-w-[160px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach($positions as $pos)
                    <option value="{{ $pos }}" @selected(request('position') === $pos)>{{ $pos }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-600">Tienda</span>
                <select name="store_id" class="mt-1 block w-full min-w-[180px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Todas</option>
                    @foreach($storesForFilter as $st)
                    <option value="{{ $st->id }}" @selected((string)request('store_id') === (string)$st->id)>{{ $st->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Filtrar</button>
            @if(request()->filled('position') || request()->filled('store_id'))
            <a href="{{ route('employees.index', request('archived') ? ['archived' => 1] : []) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpiar filtros</a>
            @endif
        </form>
    </div>
    @endif

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <p id="reorderStatus" class="mb-2 hidden text-sm text-emerald-600"></p>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm" id="employeesTable">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="drag-col hidden w-10 px-2 py-2" aria-hidden="true"></th>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">DNI</th>
                        <th class="px-3 py-2">Puesto</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2">Usuario</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody id="employees-tbody" class="divide-y divide-slate-100">
                    @forelse($employees as $employee)
                        <tr class="employee-row hover:bg-slate-50" data-employee-id="{{ $employee->id }}">
                            <td class="drag-col hidden cursor-grab px-2 py-2 align-middle text-slate-400 active:cursor-grabbing" title="Arrastrar">
                                <span class="drag-handle inline-flex text-lg leading-none select-none">⋮⋮</span>
                            </td>
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
                                    @if(($showArchived ?? false))
                                        @if(auth()->user()->hasPermission('hr.employees.edit') || auth()->user()->hasPermission('hr.employees.delete'))
                                        <form method="POST" action="{{ route('employees.restore', $employee->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                                Restaurar
                                            </button>
                                        </form>
                                        @endif
                                    @else
                                        @if(auth()->user()->hasPermission('hr.employees.delete'))
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" data-confirm-title="Archivar empleado" data-confirm-message="¿Archivar a este empleado? No se borrarán sus datos y podrás restaurarlo desde la lista de archivados." data-confirm-ok="Archivar">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-50">
                                                Archivar
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">{{ $showArchived ?? false ? 'No hay empleados archivados' : 'No hay empleados registrados' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if($canReorder ?? false)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function() {
    var tbody = document.getElementById('employees-tbody');
    var btn = document.getElementById('btnToggleReorder');
    var label = document.getElementById('btnToggleReorderLabel');
    var table = document.getElementById('employeesTable');
    var statusEl = document.getElementById('reorderStatus');
    if (!tbody || !btn || tbody.querySelectorAll('.employee-row').length < 2) return;

    var sortable = null;
    var reorderActive = false;

    function showStatus(msg, ok) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.classList.remove('hidden', 'text-emerald-600', 'text-rose-600');
        statusEl.classList.add(ok ? 'text-emerald-600' : 'text-rose-600');
        statusEl.classList.remove('hidden');
        if (ok) setTimeout(function() { statusEl.classList.add('hidden'); }, 2500);
    }

    function collectOrder() {
        return Array.prototype.map.call(tbody.querySelectorAll('tr.employee-row'), function(tr) {
            return parseInt(tr.getAttribute('data-employee-id'), 10);
        });
    }

    function saveOrder() {
        var order = collectOrder();
        fetch('{{ route("employees.reorder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ order: order })
        }).then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
            if (res.ok && res.data.ok) showStatus('Orden guardado.', true);
            else showStatus(res.data.message || 'No se pudo guardar el orden.', false);
        }).catch(function() { showStatus('Error de red.', false); });
    }

    btn.addEventListener('click', function() {
        reorderActive = !reorderActive;
        table.classList.toggle('reorder-mode', reorderActive);
        document.querySelectorAll('.drag-col').forEach(function(el) {
            el.classList.toggle('hidden', !reorderActive);
        });
        if (reorderActive) {
            label.textContent = 'Listo (orden guardado al soltar)';
            if (typeof Sortable !== 'undefined') {
                sortable = Sortable.create(tbody, {
                    handle: '.drag-handle',
                    animation: 150,
                    draggable: '.employee-row',
                    onEnd: function() { saveOrder(); }
                });
            }
        } else {
            label.textContent = 'Ordenar empleados';
            if (sortable) { sortable.destroy(); sortable = null; }
        }
    });
})();
</script>
@endpush
@endif
@endsection
