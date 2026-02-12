@extends('layouts.app')

@section('title', 'Crear Traspaso')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Crear Traspaso</h1>
                <p class="text-sm text-slate-500">Nueva transferencia entre tiendas y carteras</p>
            </div>
            <a href="{{ route('transfers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver
            </a>
        </div>
    </header>

    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-6" id="transferForm">
        @csrf

        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="text-md font-semibold text-slate-900 mb-4">Información básica</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha <span class="text-rose-500">*</span></span>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 @error('date') border-rose-300 @enderror"/>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Importe <span class="text-rose-500">*</span></span>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 @error('amount') border-rose-300 @enderror"/>
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
                        <option value="store" {{ old('origin_type') === 'store' ? 'selected' : '' }}>Tienda</option>
                        <option value="wallet" {{ old('origin_type') === 'wallet' ? 'selected' : '' }}>Cartera</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Origen <span class="text-rose-500">*</span></span>
                    <select name="origin_id" id="origin_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('origin_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fondo <span class="text-rose-500">*</span></span>
                    <select name="origin_fund" id="origin_fund" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="cash" {{ old('origin_fund') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="bank" {{ old('origin_fund') === 'bank' ? 'selected' : '' }}>Banco</option>
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
                        <option value="store" {{ old('destination_type') === 'store' ? 'selected' : '' }}>Tienda</option>
                        <option value="wallet" {{ old('destination_type') === 'wallet' ? 'selected' : '' }}>Cartera</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Destino <span class="text-rose-500">*</span></span>
                    <select name="destination_id" id="destination_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('destination_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fondo <span class="text-rose-500">*</span></span>
                    <select name="destination_fund" id="destination_fund" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="cash" {{ old('destination_fund') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                        <option value="bank" {{ old('destination_fund') === 'bank' ? 'selected' : '' }}>Banco</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <h2 class="text-md font-semibold text-slate-900 mb-4">Configuración</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Estado <span class="text-rose-500">*</span></span>
                    <select name="status" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="pending" {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="reconciled" {{ old('status') === 'reconciled' ? 'selected' : '' }}>Conciliado (aplicar inmediatamente)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Si seleccionas "Conciliado", la transferencia se aplicará automáticamente al guardar.</p>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Notas</span>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">{{ old('notes') }}</textarea>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('transfers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Crear traspaso
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const originType = document.getElementById('origin_type');
    const originId = document.getElementById('origin_id');
    const originFund = document.getElementById('origin_fund');
    const destinationType = document.getElementById('destination_type');
    const destinationId = document.getElementById('destination_id');
    const destinationFund = document.getElementById('destination_fund');
    const form = document.getElementById('transferForm');
    
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
    
    function validateCombination() {
        const originTypeVal = originType.value;
        const destinationTypeVal = destinationType.value;
        const originFundVal = originFund.value;
        const destinationFundVal = destinationFund.value;
        const originIdVal = originId.value;
        const destinationIdVal = destinationId.value;
        
        // Validar que origen ≠ destino
        if (originTypeVal === destinationTypeVal && 
            originIdVal === destinationIdVal &&
            originFundVal === destinationFundVal) {
            alert('El origen y el destino no pueden ser iguales.');
            return false;
        }
        
        // Validar combinaciones permitidas
        // Caso 1: Banco → Banco (solo tienda a tienda)
        if (originFundVal === 'bank' && destinationFundVal === 'bank') {
            if (originTypeVal !== 'store' || destinationTypeVal !== 'store') {
                alert('Las transferencias Banco → Banco solo pueden ser entre tiendas.');
                return false;
            }
        }
        
        // Caso 2: Efectivo → Banco (tienda o cartera a tienda)
        if (originFundVal === 'cash' && destinationFundVal === 'bank') {
            if (destinationTypeVal !== 'store') {
                alert('El destino de Efectivo → Banco debe ser una tienda.');
                return false;
            }
        }
        
        // Caso 3: Banco → Efectivo (tienda a tienda o cartera)
        if (originFundVal === 'bank' && destinationFundVal === 'cash') {
            if (originTypeVal !== 'store') {
                alert('El origen de Banco → Efectivo debe ser una tienda.');
                return false;
            }
        }
        
        return true;
    }
    
    // Actualizar selectores cuando cambia el tipo
    originType.addEventListener('change', function() {
        updateSelect(originId, this.value, originId.value);
        validateCombination();
    });
    
    destinationType.addEventListener('change', function() {
        updateSelect(destinationId, this.value, destinationId.value);
        validateCombination();
    });
    
    // Validar al cambiar fondos
    originFund.addEventListener('change', validateCombination);
    destinationFund.addEventListener('change', validateCombination);
    originId.addEventListener('change', validateCombination);
    destinationId.addEventListener('change', validateCombination);
    
    // Validar antes de enviar
    form.addEventListener('submit', function(e) {
        if (!validateCombination()) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endsection
