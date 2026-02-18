@extends('layouts.app')

@section('title', 'Editar Pedido')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Editar Pedido</h1>
                <p class="text-sm text-slate-500">Modifica los datos del pedido</p>
            </div>
            @if($order->supplier_id)
            <a href="{{ route('orders.supplier', $order->supplier) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Volver a {{ $order->supplier->name }}
            </a>
            @else
            <a href="{{ route('orders.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Volver
            </a>
            @endif
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('orders.update', $order) }}" class="space-y-6" id="orderForm">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', $order->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Proveedor *</span>
                    <select name="supplier_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @foreach($suppliers ?? [] as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $order->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                    <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id', $order->store_id) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Número de factura</span>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number', $order->invoice_number) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Número de pedido *</span>
                    <input type="text" name="order_number" value="{{ old('order_number', $order->order_number) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Concepto *</span>
                    <select name="concept" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="pedido" {{ old('concept', $order->concept) === 'pedido' ? 'selected' : '' }}>Pedido</option>
                        <option value="royalty" {{ old('concept', $order->concept) === 'royalty' ? 'selected' : '' }}>Royalty</option>
                        <option value="rectificacion" {{ old('concept', $order->concept) === 'rectificacion' ? 'selected' : '' }}>Rectificación</option>
                        <option value="tara" {{ old('concept', $order->concept) === 'tara' ? 'selected' : '' }}>Tara</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                    <input type="number" name="amount" id="orderAmount" step="0.01" min="0" value="{{ old('amount', $order->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>

            <!-- División entre tiendas -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 ring-1 ring-slate-100">
                <label class="flex items-center gap-2 mb-4">
                    <input type="checkbox" id="orderSplitStores" {{ $order->store_split ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500"/>
                    <span class="text-sm font-semibold text-slate-700">Dividir este pedido entre tiendas</span>
                </label>
                <div id="orderSplitContainer" class="{{ $order->store_split ? '' : 'hidden' }} space-y-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-700 mb-2">Selecciona las tiendas que participan:</div>
                        <div id="orderSplitStoreCheckboxes" class="space-y-2">
                            @php
                                $splitStores = $order->store_split['stores'] ?? [];
                            @endphp
                            @foreach($stores as $store)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="order_split_stores[]" value="{{ $store->id }}" {{ in_array($store->id, $splitStores) ? 'checked' : '' }} class="order-split-store-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500"/>
                                <span class="text-sm text-slate-700">{{ $store->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Distribución por tienda:</span>
                        <span class="text-slate-600">
                            Total: <span id="orderSplitTotal" class="font-semibold text-brand-700">0,00 €</span>
                        </span>
                    </div>
                    <div id="orderSplitStoresList" class="space-y-2">
                        <!-- Se llena dinámicamente -->
                    </div>
                    <div id="orderSplitError" class="hidden rounded-lg bg-rose-50 p-2 text-xs text-rose-700 ring-1 ring-rose-200">
                        La suma de las cantidades debe ser igual al importe total del pedido.
                    </div>
                </div>
            </div>

            <!-- Sección de Pagos -->
            <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-emerald-700">
                            <path d="M4 10h12M4 14h9M19 6a7.7 7.7 0 0 0-5.2-2A7.9 7.9 0 0 0 6 12c0 4.4 3.5 8 7.8 8 2 0 3.8-.8 5.2-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="text-sm font-semibold text-emerald-900">Pagos</span>
                    </div>
                    <button type="button" id="addPaymentBtn" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Añadir pago
                    </button>
                </div>

                <div id="paymentsContainer" class="space-y-3">
                    @foreach($order->payments as $index => $payment)
                        <div class="rounded-xl border border-slate-200 bg-white p-3 ring-1 ring-slate-100" data-payment-index="{{ $index }}">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-700">Fecha de pago *</span>
                                    <input type="date" name="payments[{{ $index }}][date]" value="{{ $payment->date->format('Y-m-d') }}" required class="payment-date mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                                </label>
                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-700">Forma de pago *</span>
                                    <select name="payments[{{ $index }}][method]" required class="payment-method mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                        <option value="cash" {{ $payment->method === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                        <option value="bank" {{ $payment->method === 'bank' ? 'selected' : '' }}>Banco</option>
                                        <option value="transfer" {{ $payment->method === 'transfer' ? 'selected' : '' }}>Transferencia</option>
                                        <option value="card" {{ $payment->method === 'card' ? 'selected' : '' }}>Tarjeta</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                                    <input type="number" name="payments[{{ $index }}][amount]" step="0.01" min="0" value="{{ $payment->amount }}" required class="payment-amount mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                                </label>
                                <div class="flex items-end">
                                    <button type="button" onclick="this.closest('div[data-payment-index]').remove(); updatePaymentTotals();" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                            @if($payment->method === 'cash')
                            <div class="payment-cash-source mt-3" data-payment-index="{{ $index }}">
                                <span class="text-xs font-semibold text-slate-700">Procedencia del efectivo *</span>
                                <div class="mt-1 flex flex-wrap gap-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payments[{{ $index }}][cash_source]" value="wallet" {{ $payment->cash_source === 'wallet' ? 'checked' : '' }} class="payment-cash-source-radio"/>
                                        <span class="text-sm">Cartera</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payments[{{ $index }}][cash_source]" value="store" {{ $payment->cash_source === 'store' ? 'checked' : '' }} class="payment-cash-source-radio"/>
                                        <span class="text-sm">Tienda</span>
                                    </label>
                                </div>
                                <div class="payment-cash-wallet mt-2 {{ $payment->cash_source !== 'wallet' ? 'hidden' : '' }}">
                                    <select name="payments[{{ $index }}][cash_wallet_id]" class="payment-cash-wallet-select mt-1 w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                        <option value="">Seleccione cartera</option>
                                        @foreach($cashWallets ?? [] as $w)
                                            <option value="{{ $w->id }}" {{ $payment->cash_wallet_id == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="payment-cash-store mt-2 {{ $payment->cash_source !== 'store' ? 'hidden' : '' }}">
                                    <select name="payments[{{ $index }}][cash_store_id]" class="payment-cash-store-select mt-1 w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                        <option value="">Seleccione tienda</option>
                                        @foreach($stores as $s)
                                            <option value="{{ $s->id }}" {{ $payment->cash_store_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @else
                            <div class="payment-cash-source mt-3 hidden" data-payment-index="{{ $index }}">
                                <span class="text-xs font-semibold text-slate-700">Procedencia del efectivo *</span>
                                <div class="mt-1 flex flex-wrap gap-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payments[{{ $index }}][cash_source]" value="wallet" class="payment-cash-source-radio"/>
                                        <span class="text-sm">Cartera</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="payments[{{ $index }}][cash_source]" value="store" class="payment-cash-source-radio"/>
                                        <span class="text-sm">Tienda</span>
                                    </label>
                                </div>
                                <div class="payment-cash-wallet mt-2 hidden">
                                    <select name="payments[{{ $index }}][cash_wallet_id]" class="payment-cash-wallet-select mt-1 w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                        <option value="">Seleccione cartera</option>
                                        @foreach($cashWallets ?? [] as $w)
                                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="payment-cash-store mt-2 hidden">
                                    <select name="payments[{{ $index }}][cash_store_id]" class="payment-cash-store-select mt-1 w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                        <option value="">Seleccione tienda</option>
                                        @foreach($stores as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 rounded-xl bg-white p-3 ring-1 ring-slate-100">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-700">Total pagado:</span>
                        <span id="totalPaidDisplay" class="font-semibold text-emerald-700">0,00 €</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-700">Importe del pedido:</span>
                        <span id="orderAmountDisplay" class="font-semibold text-slate-900">0,00 €</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm border-t border-slate-200 pt-2">
                        <span class="font-semibold text-slate-700">Pendiente:</span>
                        <span id="pendingAmountDisplay" class="font-semibold text-amber-700">0,00 €</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('orders.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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
let paymentIndex = {{ $order->payments->count() }};

const storesData = @json($stores);
const existingSplit = @json($order->store_split ?? null);

// Manejo de división de pedidos entre tiendas
const orderSplitStores = document.getElementById('orderSplitStores');
const orderSplitContainer = document.getElementById('orderSplitContainer');
const orderAmountInput = document.getElementById('orderAmount');
const orderSplitStoreCheckboxes = document.querySelectorAll('.order-split-store-checkbox');
const orderSplitStoresList = document.getElementById('orderSplitStoresList');
const orderSplitTotal = document.getElementById('orderSplitTotal');
const orderSplitError = document.getElementById('orderSplitError');

function updateOrderSplit() {
    if (!orderSplitStores.checked) {
        orderSplitContainer.classList.add('hidden');
        return;
    }
    
    orderSplitContainer.classList.remove('hidden');
    
    const selectedStores = Array.from(orderSplitStoreCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => {
            const store = storesData.find(s => s.id == cb.value);
            return { id: store.id, name: store.name };
        });
    
    if (selectedStores.length === 0) {
        orderSplitStoresList.innerHTML = '<p class="text-xs text-slate-500">Selecciona al menos una tienda</p>';
        orderSplitTotal.textContent = '0,00 €';
        return;
    }
    
    const totalAmount = parseFloat(orderAmountInput.value) || 0;
    const amountPerStore = totalAmount / selectedStores.length;
    
    orderSplitStoresList.innerHTML = selectedStores.map((store, index) => {
        // Si hay datos existentes, usar esos valores, sino dividir por partes iguales
        let amount = amountPerStore.toFixed(2);
        if (existingSplit && existingSplit.amounts && existingSplit.amounts[store.id]) {
            amount = parseFloat(existingSplit.amounts[store.id]).toFixed(2);
        }
        
        return `
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
                <div class="flex-1">
                    <div class="text-xs font-semibold text-slate-700">${store.name}</div>
                </div>
                <div class="w-32">
                    <input 
                        type="number" 
                        name="order_split_amounts[${store.id}]" 
                        step="0.01" 
                        min="0" 
                        value="${amount}"
                        class="order-split-amount w-full rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm outline-none ring-brand-200 focus:ring-2"
                        data-store-id="${store.id}"
                    />
                </div>
                <div class="w-20 text-right text-xs font-semibold text-slate-600">
                    <span class="order-split-percentage">${totalAmount > 0 ? ((parseFloat(amount) / totalAmount) * 100).toFixed(1) : '0.0'}%</span>
                </div>
            </div>
        `;
    }).join('');
    
    // Añadir event listeners a los inputs de cantidad
    document.querySelectorAll('.order-split-amount').forEach(input => {
        input.addEventListener('input', validateOrderSplit);
    });
    
    updateOrderSplitTotal();
}

function validateOrderSplit() {
    const totalAmount = parseFloat(orderAmountInput.value) || 0;
    const splitAmounts = Array.from(document.querySelectorAll('.order-split-amount'))
        .map(input => parseFloat(input.value) || 0);
    const sum = splitAmounts.reduce((a, b) => a + b, 0);
    
    const difference = Math.abs(sum - totalAmount);
    const tolerance = 0.01;
    
    if (difference > tolerance) {
        orderSplitError.classList.remove('hidden');
    } else {
        orderSplitError.classList.add('hidden');
    }
    
    updateOrderSplitTotal();
}

function updateOrderSplitTotal() {
    const splitAmounts = Array.from(document.querySelectorAll('.order-split-amount'))
        .map(input => parseFloat(input.value) || 0);
    const sum = splitAmounts.reduce((a, b) => a + b, 0);
    
    orderSplitTotal.textContent = sum.toFixed(2).replace('.', ',') + ' €';
    
    // Actualizar porcentajes
    const totalAmount = parseFloat(orderAmountInput.value) || 0;
    if (totalAmount > 0) {
        document.querySelectorAll('.order-split-amount').forEach(input => {
            const amount = parseFloat(input.value) || 0;
            const percentage = (amount / totalAmount * 100).toFixed(1);
            const percentageEl = input.closest('.flex').querySelector('.order-split-percentage');
            if (percentageEl) {
                percentageEl.textContent = percentage + '%';
            }
        });
    }
}

if (orderSplitStores) {
    orderSplitStores.addEventListener('change', updateOrderSplit);
    
    // Inicializar si está marcado
    if (orderSplitStores.checked) {
        updateOrderSplit();
    }
}

if (orderAmountInput) {
    orderAmountInput.addEventListener('input', function() {
        if (orderSplitStores && orderSplitStores.checked) {
            updateOrderSplit();
        }
        updatePaymentTotals();
    });
}

if (orderSplitStoreCheckboxes.length > 0) {
    orderSplitStoreCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (orderSplitStores && orderSplitStores.checked) {
                updateOrderSplit();
            }
        });
    });
}

// Prevenir que el formulario se envíe si la división no suma correctamente
const orderForm = document.getElementById('orderForm');
if (orderForm) {
    orderForm.addEventListener('submit', function(e) {
        if (orderSplitStores && orderSplitStores.checked) {
            const totalAmount = parseFloat(orderAmountInput.value) || 0;
            const splitAmounts = Array.from(document.querySelectorAll('.order-split-amount'))
                .map(input => parseFloat(input.value) || 0);
            const sum = splitAmounts.reduce((a, b) => a + b, 0);
            
            const difference = Math.abs(sum - totalAmount);
            const tolerance = 0.01;
            
            if (difference > tolerance) {
                e.preventDefault();
                orderSplitError.classList.remove('hidden');
                orderSplitError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                alert('La suma de las cantidades debe ser igual al importe total del pedido.');
                return false;
            }
        }
    });
}

document.getElementById('addPaymentBtn').addEventListener('click', function() {
    addPaymentRow();
});

document.getElementById('orderAmount').addEventListener('input', updatePaymentTotals);
document.querySelectorAll('.payment-amount, .payment-date').forEach(input => {
    input.addEventListener('input', updatePaymentTotals);
});

function addPaymentRow(payment = null) {
    const container = document.getElementById('paymentsContainer');
    const index = paymentIndex++;
    
    const row = document.createElement('div');
    row.className = 'rounded-xl border border-slate-200 bg-white p-3 ring-1 ring-slate-100';
    row.dataset.paymentIndex = index;
    
    row.innerHTML = `
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Fecha de pago *</span>
                <input type="date" name="payments[${index}][date]" value="${payment?.date || ''}" required class="payment-date mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Forma de pago *</span>
                <select name="payments[${index}][method]" required class="payment-method mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Selecciona...</option>
                    <option value="cash" ${payment?.method === 'cash' ? 'selected' : ''}>Efectivo</option>
                    <option value="bank" ${payment?.method === 'bank' ? 'selected' : ''}>Banco</option>
                    <option value="transfer" ${payment?.method === 'transfer' ? 'selected' : ''}>Transferencia</option>
                    <option value="card" ${payment?.method === 'card' ? 'selected' : ''}>Tarjeta</option>
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                <input type="number" name="payments[${index}][amount]" step="0.01" min="0" value="${payment?.amount || ''}" required class="payment-amount mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <div class="flex items-end">
                <button type="button" onclick="this.closest('div[data-payment-index]').remove(); updatePaymentTotals();" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                    Eliminar
                </button>
            </div>
        </div>
    `;
    
        <div class="payment-cash-source mt-3 hidden" data-payment-index="${index}">
            <span class="text-xs font-semibold text-slate-700">Procedencia del efectivo *</span>
            <div class="mt-1 flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="payments[${index}][cash_source]" value="wallet" class="payment-cash-source-radio"/>
                    <span class="text-sm">Cartera</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="payments[${index}][cash_source]" value="store" class="payment-cash-source-radio"/>
                    <span class="text-sm">Tienda</span>
                </label>
            </div>
            <div class="payment-cash-wallet mt-2 hidden">
                <select name="payments[${index}][cash_wallet_id]" class="payment-cash-wallet-select mt-1 w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Seleccione cartera</option>
                </select>
            </div>
            <div class="payment-cash-store mt-2 hidden">
                <select name="payments[${index}][cash_store_id]" class="payment-cash-store-select mt-1 w-full max-w-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                    <option value="">Seleccione tienda</option>
                </select>
            </div>
        </div>
    `;
    
    container.appendChild(row);
    
    const methodSelect = row.querySelector('.payment-method');
    const cashSourceBlock = row.querySelector('.payment-cash-source');
    const walletBlock = row.querySelector('.payment-cash-wallet');
    const storeBlock = row.querySelector('.payment-cash-store');
    const walletSelect = row.querySelector('.payment-cash-wallet-select');
    const storeSelect = row.querySelector('.payment-cash-store-select');
    
    walletSelect.innerHTML = '<option value="">Seleccione cartera</option>' + cashWalletsForPayments.map(w => `<option value="${w.id}">${w.name}</option>`).join('');
    storeSelect.innerHTML = '<option value="">Seleccione tienda</option>' + storesForPayments.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
    
    function toggleCashSource() {
        const isCash = methodSelect.value === 'cash';
        cashSourceBlock.classList.toggle('hidden', !isCash);
        if (!isCash) {
            row.querySelectorAll('.payment-cash-source-radio').forEach(r => { r.checked = false; });
            walletSelect.value = ''; storeSelect.value = '';
            walletBlock.classList.add('hidden'); storeBlock.classList.add('hidden');
        }
    }
    methodSelect.addEventListener('change', toggleCashSource);
    row.querySelectorAll('.payment-cash-source-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            walletBlock.classList.toggle('hidden', this.value !== 'wallet');
            storeBlock.classList.toggle('hidden', this.value !== 'store');
            if (this.value !== 'wallet') walletSelect.value = '';
            if (this.value !== 'store') storeSelect.value = '';
        });
    });
    toggleCashSource();
    
    row.querySelectorAll('.payment-amount, .payment-date').forEach(input => {
        input.addEventListener('input', updatePaymentTotals);
    });
    
    updatePaymentTotals();
}

function initPaymentCashSourceToggles() {
    document.querySelectorAll('#paymentsContainer .rounded-xl').forEach(row => {
        const methodSelect = row.querySelector('.payment-method');
        const cashSourceBlock = row.querySelector('.payment-cash-source');
        const walletBlock = row.querySelector('.payment-cash-wallet');
        const storeBlock = row.querySelector('.payment-cash-store');
        const walletSelect = row.querySelector('.payment-cash-wallet-select');
        const storeSelect = row.querySelector('.payment-cash-store-select');
        if (!methodSelect || !cashSourceBlock) return;
        function toggle() {
            const isCash = methodSelect.value === 'cash';
            cashSourceBlock.classList.toggle('hidden', !isCash);
        }
        methodSelect.addEventListener('change', toggle);
        if (walletBlock && storeBlock) {
            row.querySelectorAll('.payment-cash-source-radio').forEach(radio => {
                radio.addEventListener('change', function() {
                    walletBlock.classList.toggle('hidden', this.value !== 'wallet');
                    storeBlock.classList.toggle('hidden', this.value !== 'store');
                });
            });
        }
        toggle();
    });
}
initPaymentCashSourceToggles();
    const orderAmount = parseFloat(document.getElementById('orderAmount').value) || 0;
    const container = document.getElementById('paymentsContainer');
    let totalPaid = 0;
    
    container.querySelectorAll('.payment-amount').forEach(input => {
        totalPaid += parseFloat(input.value) || 0;
    });
    
    const pending = Math.max(0, orderAmount - totalPaid);
    
    document.getElementById('totalPaidDisplay').textContent = formatEuro(totalPaid);
    document.getElementById('orderAmountDisplay').textContent = formatEuro(orderAmount);
    document.getElementById('pendingAmountDisplay').textContent = formatEuro(pending);
    document.getElementById('pendingAmountDisplay').className = pending > 0 
        ? 'font-semibold text-amber-700' 
        : 'font-semibold text-emerald-700';
}

function formatEuro(amount) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

updatePaymentTotals();
</script>
@endpush
@endsection
