@extends('layouts.app')

@section('title', 'Editar Traspaso')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Editar Traspaso</h1>
                <p class="text-sm text-slate-500">Modificar transferencia entre tiendas y carteras</p>
            </div>
            <a href="{{ route('transfers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
        </div>
    </header>

    <form method="POST" action="{{ route('transfers.update', $transfer) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="text-md font-semibold text-slate-900 mb-4">Información básica</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha <span class="text-rose-500">*</span></span>
                    <input type="date" name="date" value="{{ old('date', $transfer->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 @error('date') border-rose-300 @enderror"/>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Importe <span class="text-rose-500">*</span></span>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $transfer->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 @error('amount') border-rose-300 @enderror"/>
                    @error('amount')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="text-md font-semibold text-slate-900 mb-4">Origen</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo <span class="text-rose-500">*</span></span>
                    <select name="origin_type" id="origin_type" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="store" {{ old('origin_type', $transfer->origin_type) === 'store' ? 'selected' : '' }}>Tienda</option>
                        <option value="wallet" {{ old('origin_type', $transfer->origin_type) === 'wallet' ? 'selected' : '' }}>Cartera</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Origen <span class="text-rose-500">*</span></span>
                    <select name="origin_id" id="origin_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @if(old('origin_type', $transfer->origin_type) === 'store')
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ old('origin_id', $transfer->origin_id) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        @else
                            @foreach($wallets as $wallet)
                                <option value="{{ $wallet->id }}" {{ old('origin_id', $transfer->origin_id) == $wallet->id ? 'selected' : '' }}>{{ $wallet->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fondo <span class="text-rose-500">*</span></span>
                    <select name="origin_fund" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="cash" {{ old('origin_fund', $transfer->origin_fund) === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="bank" {{ old('origin_fund', $transfer->origin_fund) === 'bank' ? 'selected' : '' }}>Banco</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="text-md font-semibold text-slate-900 mb-4">Destino</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo <span class="text-rose-500">*</span></span>
                    <select name="destination_type" id="destination_type" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="store" {{ old('destination_type', $transfer->destination_type) === 'store' ? 'selected' : '' }}>Tienda</option>
                        <option value="wallet" {{ old('destination_type', $transfer->destination_type) === 'wallet' ? 'selected' : '' }}>Cartera</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Destino <span class="text-rose-500">*</span></span>
                    <select name="destination_id" id="destination_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @if(old('destination_type', $transfer->destination_type) === 'store')
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ old('destination_id', $transfer->destination_id) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        @else
                            @foreach($wallets as $wallet)
                                <option value="{{ $wallet->id }}" {{ old('destination_id', $transfer->destination_id) == $wallet->id ? 'selected' : '' }}>{{ $wallet->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fondo <span class="text-rose-500">*</span></span>
                    <select name="destination_fund" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="cash" {{ old('destination_fund', $transfer->destination_fund) === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="bank" {{ old('destination_fund', $transfer->destination_fund) === 'bank' ? 'selected' : '' }}>Banco</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="text-md font-semibold text-slate-900 mb-4">Configuración</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Método <span class="text-rose-500">*</span></span>
                    <select name="method" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="manual" {{ old('method', $transfer->method) === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="bank_import" {{ old('method', $transfer->method) === 'bank_import' ? 'selected' : '' }}>Importación bancaria</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Estado <span class="text-rose-500">*</span></span>
                    <select name="status" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="pending" {{ old('status', $transfer->status) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="reconciled" {{ old('status', $transfer->status) === 'reconciled' ? 'selected' : '' }}>Conciliado</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Notas</span>
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">{{ old('notes', $transfer->notes) }}</textarea>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('transfers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const originType = document.getElementById('origin_type');
    const originId = document.getElementById('origin_id');
    const destinationType = document.getElementById('destination_type');
    const destinationId = document.getElementById('destination_id');
    
    const stores = @json($stores->pluck('name', 'id'));
    const wallets = @json($wallets->pluck('name', 'id'));
    
    function updateSelect(select, type, currentValue) {
        const options = type === 'store' ? stores : wallets;
        select.innerHTML = '';
        for (const [id, name] of Object.entries(options)) {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = name;
            if (id == currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        }
    }
    
    originType.addEventListener('change', function() {
        updateSelect(originId, this.value, '{{ old("origin_id", $transfer->origin_id) }}');
    });
    
    destinationType.addEventListener('change', function() {
        updateSelect(destinationId, this.value, '{{ old("destination_id", $transfer->destination_id) }}');
    });
});
</script>
@endsection
