@extends('layouts.app')

@section('title', 'Reparaciones - ' . $store->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <a href="{{ route('clients.repairs.index') }}" class="text-sm text-slate-500 hover:text-brand-600 mb-1 inline-block">← Reparaciones</a>
                <h1 class="text-lg font-semibold">Reparaciones — {{ $store->name }}</h1>
                <p class="text-sm text-slate-500">Listado de reparaciones para esta tienda.</p>
            </div>
            @if(auth()->user()->hasPermission('clients.repairs.create'))
            <a href="{{ route('clients.repairs.create', $store) }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Crear registro
            </a>
            @endif
        </div>
    </header>

    @if(session('success'))
    <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-100">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500 uppercase">Total pendientes</div>
            <div class="mt-1 text-2xl font-semibold text-amber-700">{{ $totalPending }}</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500 uppercase">Total completados</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-700">{{ $totalCompleted }}</div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('clients.repairs.store', $store) }}" class="flex flex-wrap items-end gap-4 mb-4">
            <label class="block min-w-[140px]">
                <span class="text-xs font-semibold text-slate-700">Estado</span>
                <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach(\App\Models\CustomerRepair::statuses() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block min-w-[140px]">
                <span class="text-xs font-semibold text-slate-700">Desde</span>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </label>
            <label class="block min-w-[140px]">
                <span class="text-xs font-semibold text-slate-700">Hasta</span>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </label>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Filtrar</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-3">Fecha</th>
                        <th class="px-3 py-3">Cliente</th>
                        <th class="px-3 py-3">Teléfono</th>
                        <th class="px-3 py-3">Artículo</th>
                        <th class="px-3 py-3">SKU</th>
                        <th class="px-3 py-3">Motivo</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-3 py-3">Fecha aviso</th>
                        <th class="px-3 py-3">Notas</th>
                        <th class="px-3 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($repairs as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2">{{ $r->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 font-medium">{{ $r->client_name }}</td>
                        <td class="px-3 py-2">{{ $r->phone ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $r->article ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $r->sku ?? '—' }}</td>
                        <td class="px-3 py-2 max-w-[150px] truncate" title="{{ $r->reason }}">{{ $r->reason ? Str::limit($r->reason, 25) : '—' }}</td>
                        <td class="px-3 py-2 status-cell">
                            @if(auth()->user()->hasPermission('clients.repairs.edit'))
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium cursor-pointer select-none status-badge {{ \App\Models\CustomerRepair::statusBadgeClass($r->status) }}"
                                  data-id="{{ $r->id }}"
                                  data-status="{{ $r->status }}"
                                  data-statuses="{{ json_encode(\App\Models\CustomerRepair::statuses()) }}"
                                  data-url="{{ route('clients.repairs.status', $r) }}"
                                  data-badge-class="{{ \App\Models\CustomerRepair::statusBadgeClass($r->status) }}"
                                  title="Clic para cambiar estado">{{ \App\Models\CustomerRepair::statuses()[$r->status] ?? $r->status }}</span>
                            <select class="hidden status-select min-w-[120px] rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium bg-white" data-id="{{ $r->id }}">
                                @foreach(\App\Models\CustomerRepair::statuses() as $key => $label)
                                    <option value="{{ $key }}" {{ $r->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @else
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ \App\Models\CustomerRepair::statusBadgeClass($r->status) }}">
                                {{ \App\Models\CustomerRepair::statuses()[$r->status] ?? $r->status }}
                            </span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $r->notification_date ? $r->notification_date->format('d/m/Y') : '' }}</td>
                        <td class="px-3 py-2 max-w-[200px] truncate" title="{{ $r->notes }}">{{ $r->notes ? Str::limit($r->notes, 30) : '—' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1">
                                @if(auth()->user()->hasPermission('clients.repairs.edit'))
                                <a href="{{ route('clients.repairs.edit', [$store, $r]) }}" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                @endif
                                @if(auth()->user()->hasPermission('clients.repairs.delete'))
                                <form method="POST" action="{{ route('clients.repairs.destroy', [$store, $r]) }}" class="inline" onsubmit="return confirm('¿Eliminar este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">Eliminar</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-3 py-8 text-center text-slate-500">No hay reparaciones.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($repairs->hasPages())
        <div class="mt-4">
            {{ $repairs->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.status-badge').forEach(function(badge) {
    badge.addEventListener('click', function() {
        var cell = this.closest('.status-cell');
        var select = cell.querySelector('.status-select');
        if (!select) return;
        this.classList.add('hidden');
        select.classList.remove('hidden');
        select.focus();
    });
});

document.querySelectorAll('.status-select').forEach(function(select) {
    select.addEventListener('change', function() {
        var cell = this.closest('.status-cell');
        var badge = cell.querySelector('.status-badge');
        var url = badge.getAttribute('data-url');
        var newStatus = this.value;
        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(function(r) {
            if (!r.ok) throw new Error('Error al guardar');
            return r.json();
        })
        .then(function(data) {
            badge.textContent = data.label;
            badge.setAttribute('data-status', data.status);
            badge.setAttribute('data-badge-class', data.badge_class);
            badge.className = 'inline-flex rounded-full px-2 py-1 text-xs font-medium cursor-pointer select-none status-badge ' + data.badge_class;
            select.classList.add('hidden');
            badge.classList.remove('hidden');
            var toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white shadow-lg';
            toast.textContent = 'Estado actualizado';
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 2000);
        })
        .catch(function() {
            select.classList.add('hidden');
            badge.classList.remove('hidden');
            alert('No se pudo actualizar el estado.');
        });
    });
    select.addEventListener('blur', function() {
        var cell = this.closest('.status-cell');
        var badge = cell.querySelector('.status-badge');
        if (badge && !this.classList.contains('hidden')) {
            this.classList.add('hidden');
            badge.classList.remove('hidden');
        }
    });
});
</script>
@endpush
@endsection
