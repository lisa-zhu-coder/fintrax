@extends('layouts.app')

@section('title', 'Editar Tienda')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Editar Tienda</h1>
                <p class="text-sm text-slate-500">{{ $store->name }}</p>
            </div>
            <a href="{{ route('company.show') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Volver
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

    <!-- Apartado Empresa -->
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Empresa</h2>
        
        <!-- Sección Cuentas Bancarias -->
        <div class="mt-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Cuentas bancarias</h3>
            </div>

            <!-- Lista de cuentas bancarias existentes -->
            @if($store->bankAccounts && $store->bankAccounts->count() > 0)
                <div class="space-y-2">
                    @foreach($store->bankAccounts as $account)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex-1">
                                <div class="font-medium text-slate-900">{{ $account->bank_name }}</div>
                                <div class="text-xs text-slate-600 mt-1">{{ $account->iban }}</div>
                            </div>
                            <form method="POST" action="{{ route('bank-accounts.destroy', $account) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta cuenta bancaria?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center text-sm text-slate-500">
                    No hay cuentas bancarias registradas
                </div>
            @endif

            <!-- Formulario para crear nueva cuenta bancaria -->
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-700">Añadir cuenta bancaria</h4>
                <form method="POST" action="{{ route('stores.bank-accounts.store', $store) }}" class="space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Nombre del banco *</span>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="Ej: Banco Santander"/>
                            @error('bank_name')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">IBAN *</span>
                            <input type="text" name="iban" value="{{ old('iban') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="ES12 3456 7890 1234 5678 9012"/>
                            @error('iban')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                            Añadir cuenta
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                                    <span class="font-medium">{{ $transfer->origin->name ?? 'Tienda #' . $transfer->origin_id }}</span>
                                @else
                                    {{ $transfer->origin->name ?? 'Cartera #' . $transfer->origin_id }}
                                @endif
                                <span class="text-xs text-slate-500 ml-1">
                                    ({{ $transfer->origin_fund === 'cash' ? 'Efectivo' : 'Banco' }})
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @if($transfer->destination_type === 'store')
                                    <span class="font-medium">{{ $transfer->destination->name ?? 'Tienda #' . $transfer->destination_id }}</span>
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
                                    @if(auth()->user()->hasPermission('treasury.transfers.edit'))
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
