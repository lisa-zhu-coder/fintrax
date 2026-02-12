@extends('layouts.app')

@section('title', 'Historial de Cartera')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">{{ $cashWallet->name }}</h1>
                <p class="text-sm text-slate-500">Historial completo de movimientos</p>
            </div>
            <a href="{{ route('cash-wallets.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Volver
            </a>
        </div>
    </header>

    <!-- Saldo actual -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="text-xs text-slate-500 mb-1">Saldo actual</div>
        <div class="text-2xl font-semibold {{ $finalBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
            {{ number_format($finalBalance, 2, ',', '.') }} €
        </div>
    </div>

    <!-- Tabla de movimientos -->
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Movimientos</h2>
        
        @if($movementsWithBalance->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                No hay movimientos registrados para esta cartera
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-3">Fecha</th>
                            <th class="px-3 py-3">Tipo</th>
                            <th class="px-3 py-3">Concepto</th>
                            <th class="px-3 py-3 text-right">Importe</th>
                            <th class="px-3 py-3 text-right">Saldo acumulado</th>
                            <th class="px-3 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($movementsWithBalance as $movement)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3 text-slate-600">
                                    {{ $movement['date']->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-3">
                                    @if($movement['type'] === 'withdrawal')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                                            Retiro
                                        </span>
                                    @elseif($movement['type'] === 'expense')
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">
                                            Gasto
                                        </span>
                                    @elseif($movement['type'] === 'transfer')
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">
                                            Transferencia
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-slate-900">
                                    {{ $movement['description'] }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    @if($movement['amount'] >= 0)
                                        <span class="font-semibold text-emerald-700">
                                            +{{ number_format($movement['amount'], 2, ',', '.') }} €
                                        </span>
                                    @else
                                        <span class="font-semibold text-rose-700">
                                            {{ number_format($movement['amount'], 2, ',', '.') }} €
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="font-semibold {{ $movement['balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($movement['balance'], 2, ',', '.') }} €
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($movement['type'] === 'withdrawal')
                                            <a href="{{ route('cash-wallets.withdrawals.edit', ['cashWallet' => $cashWallet->id, 'withdrawal' => $movement['id']]) }}" 
                                                class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Editar
                                            </a>
                                            <form method="POST" action="{{ route('cash-wallets.withdrawals.destroy', ['cashWallet' => $cashWallet->id, 'withdrawal' => $movement['id']]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este retiro? El saldo de la cartera se ajustará automáticamente.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        @elseif($movement['type'] === 'expense')
                                            <a href="{{ route('cash-wallets.expenses.edit', ['cashWallet' => $cashWallet->id, 'expense' => $movement['id']]) }}" 
                                                class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Editar
                                            </a>
                                            <form method="POST" action="{{ route('cash-wallets.expenses.destroy', ['cashWallet' => $cashWallet->id, 'expense' => $movement['id']]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este gasto? Se eliminará también el registro financiero asociado y el saldo de la cartera se ajustará automáticamente.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        @elseif($movement['type'] === 'transfer')
                                            <a href="{{ route('cash-wallets.transfers.edit', ['cashWallet' => $cashWallet->id, 'transfer' => $movement['id']]) }}" 
                                                class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Editar
                                            </a>
                                            <form method="POST" action="{{ route('cash-wallets.transfers.destroy', ['cashWallet' => $cashWallet->id, 'transfer' => $movement['id']]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta transferencia? El saldo de la cartera se ajustará automáticamente.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Traspasos relacionados -->
    @if(isset($relatedTransfers) && $relatedTransfers->isNotEmpty())
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Traspasos relacionados</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-3">Fecha</th>
                        <th class="px-3 py-3 text-right">Importe</th>
                        <th class="px-3 py-3">Origen</th>
                        <th class="px-3 py-3">Destino</th>
                        <th class="px-3 py-3">Tipo</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($relatedTransfers as $transfer)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-3">{{ $transfer->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-3 text-right font-semibold">
                                {{ number_format($transfer->amount, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-3">
                                @if($transfer->origin_type === 'store')
                                    {{ $transfer->origin->name ?? 'Tienda #' . $transfer->origin_id }}
                                @else
                                    {{ $transfer->origin->name ?? 'Cartera #' . $transfer->origin_id }}
                                @endif
                                <span class="text-xs text-slate-500 ml-1">
                                    ({{ $transfer->origin_fund === 'cash' ? 'Efectivo' : 'Banco' }})
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @if($transfer->destination_type === 'store')
                                    {{ $transfer->destination->name ?? 'Tienda #' . $transfer->destination_id }}
                                @else
                                    {{ $transfer->destination->name ?? 'Cartera #' . $transfer->destination_id }}
                                @endif
                                <span class="text-xs text-slate-500 ml-1">
                                    ({{ $transfer->destination_fund === 'cash' ? 'Efectivo' : 'Banco' }})
                                </span>
                            </td>
                            <td class="px-3 py-3">
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
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $transfer->status === 'reconciled' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $transfer->status === 'reconciled' ? 'Conciliado' : 'Pendiente' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    @if(auth()->user()->hasPermission('treasury.cash_wallets.edit'))
                                    <a href="{{ route('transfers.edit', $transfer) }}" class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    @endif
                                    <a href="{{ route('transfers.index', ['date_from' => $transfer->date->format('Y-m-d'), 'date_to' => $transfer->date->format('Y-m-d')]) }}" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100" title="Ver en listado">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
