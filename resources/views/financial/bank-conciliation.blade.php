@extends('layouts.app')

@section('title', 'Conciliación Bancaria')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Conciliación Bancaria</h1>
                <p class="text-sm text-slate-500">Concilia movimientos bancarios con registros financieros</p>
            </div>
            <a href="{{ route('financial.bank-import') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Importar movimientos
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-100">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filtros -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <form method="GET" action="{{ route('financial.bank-conciliation') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Tienda</span>
                <select name="store_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Todas las tiendas</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                            {{ $store->name }}
                        </option>
                    @endforeach
                </select>
            </label>

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
                    <option value="">Todos</option>
                    <option value="conciliado" {{ request('status') === 'conciliado' ? 'selected' : '' }}>Conciliado</option>
                    <option value="pendiente" {{ request('status') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Tipo</span>
                <select name="type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Todos</option>
                    <option value="gasto" {{ request('type') === 'gasto' ? 'selected' : '' }}>Gasto</option>
                    <option value="ingreso" {{ request('type') === 'ingreso' ? 'selected' : '' }}>Ingreso</option>
                    <option value="traspaso" {{ request('type') === 'traspaso' ? 'selected' : '' }}>Traspaso</option>
                </select>
            </label>

            <div class="flex items-end gap-2 md:col-span-2 lg:col-span-5">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Filtrar
                </button>
                <a href="{{ route('financial.bank-conciliation') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Limpiar filtros
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de movimientos -->
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($movements->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                No hay movimientos bancarios para mostrar
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-3">Fecha</th>
                            <th class="px-3 py-3">Cuenta bancaria</th>
                            <th class="px-3 py-3">Concepto del banco</th>
                            <th class="px-3 py-3 text-right">Importe</th>
                            <th class="px-3 py-3">Estado</th>
                            <th class="px-3 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($movements as $movement)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3 text-slate-600">
                                    {{ $movement->date->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-3 text-slate-900">
                                    <div class="font-medium">{{ $movement->bankAccount->bank_name ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $movement->bankAccount->iban ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-3 text-slate-900">
                                    {{ $movement->description }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    @php
                                        $amount = (float) $movement->amount;
                                        $isPositive = $movement->type === 'credit';
                                        $isTransfer = $movement->type === 'transfer';
                                    @endphp
                                    <span class="font-semibold {{ $isPositive ? 'text-emerald-700' : ($isTransfer ? 'text-amber-700' : 'text-rose-700') }}">
                                        @if($isTransfer)
                                            → {{ number_format(abs($amount), 2, ',', '.') }} €
                                        @else
                                            {{ $isPositive ? '+' : '-' }}{{ number_format(abs($amount), 2, ',', '.') }} €
                                        @endif
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    @php
                                        // Obtener el transfer relacionado usando el nuevo mapa
                                        $relatedTransfer = isset($movementToTransferMap) && isset($movementToTransferMap[$movement->id]) 
                                            ? $movementToTransferMap[$movement->id] 
                                            : ($movement->transfer_id && isset($relatedTransfers[$movement->transfer_id]) 
                                                ? $relatedTransfers[$movement->transfer_id] 
                                                : null);
                                    @endphp
                                    
                                    @if($movement->type === 'transfer' && !$movement->is_conciliated && $movement->status === 'pendiente')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                            Traspaso pendiente
                                        </span>
                                        @if($movement->destinationStore)
                                            <div class="mt-1 text-xs text-slate-500">
                                                → {{ $movement->destinationStore->name }}
                                            </div>
                                        @endif
                                        @if($relatedTransfer)
                                            <div class="mt-1">
                                                <a href="{{ route('transfers.edit', $relatedTransfer) }}" class="text-xs text-brand-600 hover:text-brand-700">
                                                    Ver traspaso #{{ $relatedTransfer->id }}
                                                </a>
                                            </div>
                                        @endif
                                    @elseif($movement->is_conciliated)
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                                            Conciliado
                                        </span>
                                        @if($movement->type === 'transfer' && $movement->destinationStore)
                                            <div class="mt-1 text-xs text-slate-500">
                                                → {{ $movement->destinationStore->name }}
                                            </div>
                                        @endif
                                        @if($relatedTransfer)
                                            <div class="mt-1">
                                                <a href="{{ route('transfers.edit', $relatedTransfer) }}" class="text-xs text-brand-600 hover:text-brand-700">
                                                    Ver traspaso #{{ $relatedTransfer->id }}
                                                </a>
                                            </div>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                            Pendiente
                                        </span>
                                        @if($relatedTransfer)
                                            <div class="mt-1">
                                                <a href="{{ route('transfers.edit', $relatedTransfer) }}" class="text-xs text-brand-600 hover:text-brand-700">
                                                    Ver traspaso #{{ $relatedTransfer->id }}
                                                </a>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('financial.bank-movements.edit', $movement) }}" 
                                            class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Editar
                                        </a>
                                        @if(!$movement->is_conciliated)
                                            @if($movement->type === 'transfer')
                                                <button type="button" 
                                                    class="btn-conciliate-transfer inline-flex items-center justify-center gap-1 rounded-xl border border-amber-600 bg-white px-3 py-1.5 text-xs font-semibold text-amber-600 hover:bg-amber-50"
                                                    data-movement-id="{{ $movement->id }}"
                                                    data-store-id="{{ $movement->bankAccount->store_id ?? '' }}"
                                                    data-store-name="{{ $movement->bankAccount->store->name ?? '' }}"
                                                    data-amount="{{ abs($movement->amount) }}"
                                                    data-sign="{{ (str_starts_with($movement->raw_description ?? '', '[NEG]')) ? 'negative' : 'positive' }}"
                                                    data-date="{{ $movement->date->format('Y-m-d') }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M17 8l-5-5-5 5M12 3v12M7 21h10a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Conciliar como traspaso
                                                </button>
                                            @else
                                                <button type="button" 
                                                    class="btn-link-expense inline-flex items-center justify-center gap-1 rounded-xl border border-brand-600 bg-white px-3 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-50"
                                                    data-movement-id="{{ $movement->id }}"
                                                    data-store-id="{{ $movement->bankAccount->store_id ?? '' }}"
                                                    data-amount="{{ abs($movement->amount) }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Enlazar gasto
                                                </button>
                                                <button type="button" 
                                                    class="btn-create-expense inline-flex items-center justify-center gap-1 rounded-xl border border-emerald-600 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50"
                                                    data-movement-id="{{ $movement->id }}"
                                                    data-description="{{ e($movement->description) }}"
                                                    data-amount="{{ abs($movement->amount) }}"
                                                    data-date="{{ $movement->date->format('Y-m-d') }}"
                                                    data-store-id="{{ $movement->bankAccount->store_id ?? '' }}">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                    Crear gasto
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('financial.bank-conciliation.ignore', $movement) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas ignorar este movimiento?')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Ignorar
                                                </button>
                                            </form>
                                        @else
                                            @if($movement->financialEntry)
                                                <a href="{{ route('financial.show', $movement->financialEntry->id) }}" class="inline-flex items-center justify-center gap-1 rounded-xl border border-brand-600 bg-white px-3 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-50">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke="currentColor" stroke-width="2"/>
                                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="2"/>
                                                    </svg>
                                                    Ver gasto #{{ $movement->financialEntry->id }}
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-400">Sin enlace</span>
                                            @endif
                                        @endif
                                        <form method="POST" action="{{ route('financial.bank-conciliation.destroy', $movement) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este movimiento bancario? Se eliminarán también todos los registros relacionados (traspasos, gastos, etc.). Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Modal para enlazar gasto -->
<div id="linkModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeLinkModal()"></div>
        
        <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Enlazar gasto existente</h3>
                <button type="button" onclick="closeLinkModal()" class="text-slate-400 hover:text-slate-600">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            
            <form id="linkForm" method="POST" action="">
                @csrf
                
                <label class="block mb-4">
                    <span class="text-xs font-semibold text-slate-700">Seleccionar gasto</span>
                    <select name="financial_entry_id" id="linkFinancialEntryId" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Cargando gastos...</option>
                    </select>
                </label>
                
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeLinkModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Enlazar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para conciliar como traspaso -->
<div id="transferModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeTransferModal()"></div>
        
        <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Conciliar como traspaso</h3>
                <button type="button" onclick="closeTransferModal()" class="text-slate-400 hover:text-slate-600">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            
            <div class="mb-4 rounded-xl bg-slate-50 p-3 text-sm">
                <div class="font-semibold text-slate-900" id="transferModalDescription"></div>
                <div class="mt-1 text-slate-600">
                    Fecha: <span id="transferModalDate"></span> | 
                    Importe: <span id="transferModalAmount"></span> €
                </div>
                <div class="mt-2 text-xs text-amber-700" id="transferModalInfo"></div>
            </div>
            
            <form id="transferForm" method="POST" action="">
                @csrf
                
                <!-- Si amount < 0: la tienda del movimiento ES el origen. Mostrar origen fijo, pedir SOLO tienda destino. -->
                <div id="transferSectionNegative" class="space-y-4 hidden">
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-sm">
                        <span class="text-xs font-semibold text-slate-600">Origen (fijo)</span>
                        <p class="font-medium text-slate-900" id="transferFixedOriginName"></p>
                    </div>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Tienda destino *</span>
                        <select name="destination_store_id" id="transferDestinationStoreId" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Selecciona...</option>
                            @if(isset($stores))
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Selecciona la tienda hacia la que va el dinero.</p>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fondo destino</span>
                        <select name="destination_fund" id="transferDestinationFund" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="bank" selected>Banco</option>
                            <option value="cash">Efectivo</option>
                        </select>
                    </label>
                </div>
                
                <!-- Si amount > 0: la tienda del movimiento ES el destino. Mostrar destino fijo, pedir SOLO tienda origen. -->
                <div id="transferSectionPositive" class="space-y-4 hidden">
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-sm">
                        <span class="text-xs font-semibold text-slate-600">Destino (fijo)</span>
                        <p class="font-medium text-slate-900" id="transferFixedDestinationName"></p>
                    </div>
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Tienda origen *</span>
                        <select name="origin_store_id" id="transferOriginStoreId" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Selecciona...</option>
                            @if(isset($stores))
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Selecciona la tienda desde la que sale el dinero (banco).</p>
                    </label>
                    <input type="hidden" name="origin_fund" value="bank">
                </div>
                
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeTransferModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                        Conciliar como traspaso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para crear gasto -->
<div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeCreateModal()"></div>
        
        <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Crear nuevo gasto</h3>
                <button type="button" onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            
            <div class="mb-4 rounded-xl bg-slate-50 p-3 text-sm">
                <div class="font-semibold text-slate-900" id="createModalDescription"></div>
                <div class="mt-1 text-slate-600">
                    Fecha: <span id="createModalDate"></span> | 
                    Importe: <span id="createModalAmount"></span> €
                </div>
            </div>
            
            <form id="createForm" method="POST" action="">
                @csrf
                
                <div class="space-y-4">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                        <input type="date" name="date" id="createDate" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                        <select name="store_id" id="createStoreId" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Selecciona...</option>
                            @if(isset($stores))
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Categoría</span>
                        <select name="expense_category" id="createCategory" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Selecciona...</option>
                            <option value="alquiler">Alquiler</option>
                            <option value="impuestos">Impuestos</option>
                            <option value="seguridad_social">Seguridad Social</option>
                            <option value="suministros">Suministros</option>
                            <option value="servicios_profesionales">Servicios profesionales</option>
                            <option value="sueldos">Sueldos</option>
                            <option value="miramira">Miramira</option>
                            <option value="mercaderia">Mercadería</option>
                            <option value="equipamiento">Equipamiento</option>
                            <option value="otros">Otros</option>
                        </select>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Concepto *</span>
                        <input type="text" name="expense_concept" id="createConcept" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Importe</span>
                        <input type="number" name="amount" id="createAmount" step="0.01" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 cursor-not-allowed"/>
                    </label>
                </div>
                
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeCreateModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Crear gasto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentMovementId = null;
const baseUrl = '{{ url("/financial/bank-conciliation") }}';
const availableExpensesUrl = '{{ url("/financial/bank-movements/available-expenses") }}';
const stores = @json($stores ?? []);

// Event listeners para botones
document.addEventListener('DOMContentLoaded', function() {
    // Botones de enlazar gasto
    document.querySelectorAll('.btn-link-expense').forEach(button => {
        button.addEventListener('click', function() {
            const movementId = this.getAttribute('data-movement-id');
            const storeId = this.getAttribute('data-store-id');
            const amount = this.getAttribute('data-amount');
            openLinkModal(movementId, storeId, amount);
        });
    });
    
    // Botones de crear gasto
    document.querySelectorAll('.btn-create-expense').forEach(button => {
        button.addEventListener('click', function() {
            const movementId = this.getAttribute('data-movement-id');
            const description = this.getAttribute('data-description');
            const amount = this.getAttribute('data-amount');
            const date = this.getAttribute('data-date');
            const storeId = this.getAttribute('data-store-id');
            openCreateModal(movementId, description, amount, date, storeId);
        });
    });
    
    // Botones de conciliar como traspaso
    document.querySelectorAll('.btn-conciliate-transfer').forEach(button => {
        button.addEventListener('click', function() {
            const movementId = this.getAttribute('data-movement-id');
            const storeId = this.getAttribute('data-store-id');
            const storeName = this.getAttribute('data-store-name') || '';
            const amount = parseFloat(this.getAttribute('data-amount'));
            const sign = this.getAttribute('data-sign') || 'positive';
            const date = this.getAttribute('data-date');
            openTransferModal(movementId, storeId, amount, date, sign, storeName);
        });
    });
});

function openLinkModal(movementId, storeId, amount) {
    currentMovementId = movementId;
    document.getElementById('linkForm').action = baseUrl + '/' + movementId + '/link-expense';
    
    // Cargar gastos disponibles filtrados por tienda e importe
    fetch(availableExpensesUrl + '?store_id=' + storeId + '&amount=' + amount)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('linkFinancialEntryId');
            select.innerHTML = '<option value="">Selecciona un gasto...</option>';
            
            if (data.expenses && data.expenses.length > 0) {
                data.expenses.forEach(expense => {
                    const option = document.createElement('option');
                    option.value = expense.id;
                    option.textContent = (expense.concept || 'Gasto') + ' - ' + parseFloat(expense.amount).toFixed(2).replace('.', ',') + ' € (' + expense.date + ')';
                    select.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No hay gastos disponibles para esta tienda e importe';
                option.disabled = true;
                select.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error cargando gastos:', error);
            document.getElementById('linkFinancialEntryId').innerHTML = '<option value="">Error al cargar gastos</option>';
        });
    
    document.getElementById('linkModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLinkModal() {
    document.getElementById('linkModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('linkForm').reset();
}

function openCreateModal(movementId, description, amount, date, storeId) {
    currentMovementId = movementId;
    document.getElementById('createForm').action = baseUrl + '/' + movementId + '/create-expense';
    
    // Establecer valores por defecto
    document.getElementById('createDate').value = date;
    document.getElementById('createStoreId').value = storeId;
    document.getElementById('createConcept').value = description;
    document.getElementById('createAmount').value = parseFloat(amount).toFixed(2);
    
    document.getElementById('createModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('createForm').reset();
}

function openTransferModal(movementId, storeId, amount, date, sign, storeName) {
    currentMovementId = movementId;
    document.getElementById('transferForm').action = baseUrl + '/' + movementId + '/conciliate-transfer';
    
    const isNegative = (sign === 'negative');
    const absAmount = typeof amount === 'number' ? amount : Math.abs(parseFloat(amount));
    
    document.getElementById('transferModalDescription').textContent = 'Movimiento bancario de traspaso';
    document.getElementById('transferModalDate').textContent = new Date(date).toLocaleDateString('es-ES');
    document.getElementById('transferModalAmount').textContent = absAmount.toFixed(2).replace('.', ',');
    
    const sectionNegative = document.getElementById('transferSectionNegative');
    const sectionPositive = document.getElementById('transferSectionPositive');
    sectionNegative.classList.add('hidden');
    sectionPositive.classList.add('hidden');
    
    if (isNegative) {
        // amount < 0: la tienda del movimiento ES el origen. Mostrar origen fijo, pedir SOLO tienda destino.
        document.getElementById('transferFixedOriginName').textContent = storeName || 'Tienda actual';
        document.getElementById('transferModalInfo').textContent = 'Origen fijo (tienda del movimiento). Selecciona la tienda destino.';
        sectionNegative.classList.remove('hidden');
        document.getElementById('transferDestinationStoreId').required = true;
        document.getElementById('transferDestinationStoreId').disabled = false;
        document.getElementById('transferDestinationFund').disabled = false;
        document.getElementById('transferOriginStoreId').required = false;
        document.getElementById('transferOriginStoreId').disabled = true;
        document.getElementById('transferOriginStoreId').value = '';
        const destSelect = document.getElementById('transferDestinationStoreId');
        destSelect.innerHTML = '<option value="">Selecciona...</option>';
        stores.forEach(store => {
            if (String(store.id) !== String(storeId)) {
                const option = document.createElement('option');
                option.value = store.id;
                option.textContent = store.name;
                destSelect.appendChild(option);
            }
        });
    } else {
        // amount > 0: la tienda del movimiento ES el destino. Mostrar destino fijo, pedir SOLO tienda origen.
        document.getElementById('transferFixedDestinationName').textContent = storeName || 'Tienda actual';
        document.getElementById('transferModalInfo').textContent = 'Destino fijo (tienda del movimiento). Selecciona la tienda origen.';
        sectionPositive.classList.remove('hidden');
        document.getElementById('transferOriginStoreId').required = true;
        document.getElementById('transferOriginStoreId').disabled = false;
        document.getElementById('transferDestinationStoreId').required = false;
        document.getElementById('transferDestinationStoreId').disabled = true;
        document.getElementById('transferDestinationFund').disabled = true;
        document.getElementById('transferDestinationStoreId').value = '';
        const origSelect = document.getElementById('transferOriginStoreId');
        origSelect.innerHTML = '<option value="">Selecciona...</option>';
        stores.forEach(store => {
            if (String(store.id) !== String(storeId)) {
                const option = document.createElement('option');
                option.value = store.id;
                option.textContent = store.name;
                origSelect.appendChild(option);
            }
        });
    }
    
    document.getElementById('transferModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('transferForm').reset();
    document.getElementById('transferDestinationStoreId').disabled = false;
    document.getElementById('transferOriginStoreId').disabled = false;
    document.getElementById('transferDestinationFund').disabled = false;
}

// Cerrar modales con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLinkModal();
        closeCreateModal();
        closeTransferModal();
    }
});
</script>
@endsection
