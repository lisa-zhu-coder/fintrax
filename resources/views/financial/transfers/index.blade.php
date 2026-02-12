@extends('layouts.app')

@section('title', 'Traspasos')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Traspasos</h1>
                <p class="text-sm text-slate-500">Gestión de transferencias entre tiendas y carteras</p>
            </div>
            @if(auth()->user()->hasPermission('treasury.transfers.create'))
            <a href="{{ route('transfers.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Crear traspaso
            </a>
            @endif
        </div>
    </header>

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('transfers.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha desde</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha hasta</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Estado</span>
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Todos</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="reconciled" {{ request('status') === 'reconciled' ? 'selected' : '' }}>Conciliado</option>
                    </select>
                </label>
                
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Método</span>
                    <select name="method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="all" {{ request('method') === 'all' || !request('method') ? 'selected' : '' }}>Todos</option>
                        <option value="manual" {{ request('method') === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="bank_import" {{ request('method') === 'bank_import' ? 'selected' : '' }}>Importación bancaria</option>
                    </select>
                </label>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Filtrar
                </button>
                <a href="{{ route('transfers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2 text-right">Importe</th>
                        <th class="px-3 py-2">Origen</th>
                        <th class="px-3 py-2">Destino</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Método</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transfers as $transfer)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">{{ $transfer->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 text-right font-semibold">
                                {{ number_format($transfer->amount, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2">
                                @if($transfer->origin_type === 'store')
                                    {{ $transfer->origin->name ?? 'Tienda #' . $transfer->origin_id }}
                                @else
                                    {{ $transfer->origin->name ?? 'Cartera #' . $transfer->origin_id }}
                                @endif
                                <span class="text-xs text-slate-500 ml-1">
                                    ({{ $transfer->origin_fund === 'cash' ? 'Efectivo' : 'Banco' }})
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                @if($transfer->destination_type === 'store')
                                    {{ $transfer->destination->name ?? 'Tienda #' . $transfer->destination_id }}
                                @else
                                    {{ $transfer->destination->name ?? 'Cartera #' . $transfer->destination_id }}
                                @endif
                                <span class="text-xs text-slate-500 ml-1">
                                    ({{ $transfer->destination_fund === 'cash' ? 'Efectivo' : 'Banco' }})
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $typeLabel = '';
                                    if ($transfer->origin_fund === 'cash' && $transfer->destination_fund === 'bank') {
                                        $typeLabel = 'Cash → Bank';
                                    } elseif ($transfer->origin_fund === 'bank' && $transfer->destination_fund === 'bank') {
                                        $typeLabel = 'Bank → Bank';
                                    } elseif ($transfer->origin_fund === 'cash' && $transfer->destination_fund === 'cash') {
                                        $typeLabel = 'Cash → Cash';
                                    } else {
                                        $typeLabel = ucfirst($transfer->origin_fund) . ' → ' . ucfirst($transfer->destination_fund);
                                    }
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="text-xs text-slate-600">
                                    {{ $transfer->method === 'manual' ? 'Manual' : 'Importación bancaria' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $transfer->status === 'reconciled' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $transfer->status === 'reconciled' ? 'Conciliado' : 'Pendiente' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    @if(auth()->user()->hasPermission('treasury.transfers.edit'))
                                    <a href="{{ route('transfers.edit', $transfer) }}" class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('treasury.transfers.delete'))
                                    <form method="POST" action="{{ route('transfers.destroy', $transfer) }}" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este traspaso?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" title="Eliminar">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-slate-500">No hay traspasos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $transfers->links() }}
        </div>
    </div>
</div>
@endsection
