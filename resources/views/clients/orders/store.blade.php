@extends('layouts.app')

@section('title', 'Pedidos clientes - ' . $store->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <a href="{{ route('clients.orders.index') }}" class="text-sm text-slate-500 hover:text-brand-600 mb-1 inline-block">← Pedidos clientes</a>
                <h1 class="text-lg font-semibold">Pedidos clientes — {{ $store->name }}</h1>
                <p class="text-sm text-slate-500">Listado de pedidos de clientes para esta tienda.</p>
            </div>
            @if(auth()->user()->hasPermission('clients.orders.create'))
            <a href="{{ route('clients.orders.create', $store) }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
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
        <form method="GET" action="{{ route('clients.orders.store', $store) }}" class="flex flex-wrap items-end gap-4 mb-4">
            <label class="block min-w-[140px]">
                <span class="text-xs font-semibold text-slate-700">Estado</span>
                <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach(\App\Models\CustomerOrder::statuses() as $key => $label)
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
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-3 py-3">Fecha aviso</th>
                        <th class="px-3 py-3">Notas</th>
                        <th class="px-3 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $o)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2">{{ $o->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 font-medium">{{ $o->client_name }}</td>
                        <td class="px-3 py-2">{{ $o->phone ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $o->article ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $o->sku ?? '—' }}</td>
                        <td class="px-3 py-2 status-cell">
                            @if(auth()->user()->hasPermission('clients.orders.edit'))
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium cursor-pointer select-none status-badge {{ \App\Models\CustomerOrder::statusBadgeClass($o->status) }}"
                                  data-id="{{ $o->id }}"
                                  data-status="{{ $o->status }}"
                                  data-statuses="{{ json_encode(\App\Models\CustomerOrder::statuses()) }}"
                                  data-url="{{ route('clients.orders.status', $o) }}"
                                  data-badge-class="{{ \App\Models\CustomerOrder::statusBadgeClass($o->status) }}"
                                  title="Clic para cambiar estado">{{ \App\Models\CustomerOrder::statuses()[$o->status] ?? $o->status }}</span>
                            <select class="hidden status-select min-w-[120px] rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium bg-white" data-id="{{ $o->id }}">
                                @foreach(\App\Models\CustomerOrder::statuses() as $key => $label)
                                    <option value="{{ $key }}" {{ $o->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @else
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ \App\Models\CustomerOrder::statusBadgeClass($o->status) }}">
                                {{ \App\Models\CustomerOrder::statuses()[$o->status] ?? $o->status }}
                            </span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $o->notification_date ? $o->notification_date->format('d/m/Y') : '' }}</td>
                        <td class="px-3 py-2 max-w-[200px] truncate" title="{{ $o->notes }}">{{ $o->notes ? Str::limit($o->notes, 30) : '—' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1">
                                @if(auth()->user()->hasPermission('clients.orders.edit'))
                                <a href="{{ route('clients.orders.edit', [$store, $o]) }}" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                @endif
                                @if(auth()->user()->hasPermission('clients.orders.delete'))
                                <form method="POST" action="{{ route('clients.orders.destroy', [$store, $o]) }}" class="inline" onsubmit="return confirm('¿Eliminar este registro?');">
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
                        <td colspan="9" class="px-3 py-8 text-center text-slate-500">No hay pedidos de clientes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="mt-4">
            {{ $orders->links() }}
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
        var statuses = JSON.parse(badge.getAttribute('data-statuses') || '{}');
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
