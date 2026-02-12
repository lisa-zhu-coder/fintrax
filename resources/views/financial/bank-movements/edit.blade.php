@extends('layouts.app')

@section('title', 'Editar Movimiento Bancario')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Editar Movimiento Bancario</h1>
                <p class="text-sm text-slate-500">{{ $bankMovement->bankAccount->bank_name ?? '—' }}</p>
            </div>
            <a href="{{ route('financial.bank-conciliation') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
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

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('financial.bank-movements.update', $bankMovement) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', $bankMovement->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Importe *</span>
                    <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount', $bankMovement->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('amount')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block md:col-span-2">
                    <span class="text-xs font-semibold text-slate-700">Concepto *</span>
                    <input type="text" name="description" value="{{ old('description', $bankMovement->description) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('description')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    @if($bankMovement->raw_description)
                        <p class="mt-1 text-xs text-slate-500">Descripción original: {{ $bankMovement->raw_description }}</p>
                    @endif
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo *</span>
                    <select name="type" id="type-select" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @php
                            $currentType = old('type', $bankMovement->type);
                            // Normalizar tipos para mostrar: expense/income -> debit/credit
                            if ($currentType === 'expense') $currentType = 'debit';
                            if ($currentType === 'income') $currentType = 'credit';
                        @endphp
                        <option value="credit" {{ $currentType === 'credit' ? 'selected' : '' }}>Ingreso</option>
                        <option value="debit" {{ $currentType === 'debit' ? 'selected' : '' }}>Gasto</option>
                        <option value="transfer" {{ $currentType === 'transfer' ? 'selected' : '' }}>Traspaso</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block md:col-span-2" id="destination-store-label" style="display: {{ old('type', $bankMovement->type) === 'transfer' ? 'block' : 'none' }};">
                    <span class="text-xs font-semibold text-slate-700">
                        @if($bankMovement->type === 'transfer')
                            @php
                                // Determinar si es traspaso entrante o saliente basado en si tiene destination_store_id
                                // Si no tiene destination_store_id y el importe original era positivo, es entrante
                                // Por ahora, asumimos que si no tiene destination_store_id, es saliente
                                $isIncoming = $bankMovement->destination_store_id && $bankMovement->bankAccount->store_id;
                            @endphp
                            @if($isIncoming)
                                Tienda origen (este traspaso es entrante a {{ $bankMovement->bankAccount->store->name ?? '—' }})
                            @else
                                Tienda destino (este traspaso sale de {{ $bankMovement->bankAccount->store->name ?? '—' }})
                            @endif
                        @else
                            Tienda destino
                        @endif
                    </span>
                    <select name="destination_store_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona una tienda...</option>
                        @foreach($stores as $store)
                            @if($store->id != $bankMovement->bankAccount->store_id)
                                <option value="{{ $store->id }}" {{ old('destination_store_id', $bankMovement->destination_store_id) == $store->id ? 'selected' : '' }}>
                                    {{ $store->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('destination_store_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    @if($bankMovement->type === 'transfer')
                        <p class="mt-1 text-xs text-slate-500">
                            Tienda actual: <strong>{{ $bankMovement->bankAccount->store->name ?? '—' }}</strong>
                        </p>
                    @endif
                </label>
            </div>

            @if($bankMovement->type === 'transfer' && $bankMovement->status === 'pendiente' && $bankMovement->destination_store_id)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-800 mb-2">Traspaso pendiente de confirmación</p>
                    <p class="text-xs text-amber-700 mb-3">
                        Este traspaso está pendiente. Al confirmarlo, se ajustará el saldo bancario de ambas tiendas.
                    </p>
                    <form method="POST" action="{{ route('financial.bank-movements.confirm-transfer', $bankMovement) }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            Confirmar traspaso
                        </button>
                    </form>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('financial.bank-conciliation') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type-select');
        const destinationStoreLabel = document.getElementById('destination-store-label');
        
        if (typeSelect && destinationStoreLabel) {
            typeSelect.addEventListener('change', function() {
                if (this.value === 'transfer') {
                    destinationStoreLabel.style.display = 'block';
                } else {
                    destinationStoreLabel.style.display = 'none';
                }
            });
        }
    });
</script>
@endpush
@endsection
