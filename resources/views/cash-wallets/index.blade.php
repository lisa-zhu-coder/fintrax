@extends('layouts.app')

@section('title', 'Carteras de Efectivo')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Carteras de Efectivo</h1>
                <p class="text-sm text-slate-500">Gestiona las carteras para la recogida de efectivo</p>
            </div>
            @if(auth()->user()->hasPermission('treasury.cash_wallets.create'))
            <a href="{{ route('cash-wallets.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Añadir cartera
            </a>
            @endif
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
        @if($walletsWithBalance->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                No hay carteras registradas
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-3">Cartera</th>
                            <th class="px-3 py-3 text-right">Saldo</th>
                            <th class="px-3 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($walletsWithBalance as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3">
                                    <a href="{{ route('cash-wallets.show', $item['wallet']) }}" class="font-semibold text-slate-900 hover:text-brand-600">
                                        {{ $item['wallet']->name }}
                                    </a>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="font-semibold text-slate-900">
                                        {{ number_format($item['balance'], 2, ',', '.') }} €
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('cash-wallets.show', $item['wallet']) }}" class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                            Ver historial
                                        </a>
                                        <button type="button" onclick="openExpenseModal({{ $item['wallet']->id }}, '{{ $item['wallet']->name }}')" class="inline-flex items-center justify-center gap-1 rounded-xl border border-brand-600 bg-white px-3 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-50">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                            Registrar gasto
                                        </button>
                                        <button type="button" onclick="openTransferModal({{ $item['wallet']->id }}, '{{ $item['wallet']->name }}')" class="inline-flex items-center justify-center gap-1 rounded-xl border border-emerald-600 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M17 8h4M3 12h18M3 16h4m10 0h4M3 8h4m10 0h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                <circle cx="12" cy="12" r="2" fill="currentColor"/>
                                            </svg>
                                            Ingresar en banco
                                        </button>
                                        <a href="{{ route('cash-wallets.edit', $item['wallet']) }}" class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('cash-wallets.destroy', $item['wallet']) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta cartera?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
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

<!-- Modal para registrar gasto -->
<div id="expenseModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeExpenseModal()"></div>
        
        <!-- Modal panel -->
        <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all w-full max-w-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900" id="expenseModalTitle">Registrar Gasto</h3>
                    <p class="text-sm text-slate-500" id="expenseModalSubtitle"></p>
                </div>
                <button type="button" onclick="closeExpenseModal()" class="rounded-xl border border-slate-200 bg-white p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <form id="expenseForm" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="cash_wallet_id" id="expenseCashWalletId">
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                            <input type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                            <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona una tienda</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Proveedor</span>
                            <select name="supplier_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona un proveedor (opcional)</option>
                                @foreach($suppliers ?? [] as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Categoría</span>
                            <select name="expense_category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
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
                            <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                            <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="0.00"/>
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Concepto</span>
                        <input type="text" name="concept" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="Descripción del gasto"/>
                    </label>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" onclick="closeExpenseModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openExpenseModal(walletId, walletName) {
    document.getElementById('expenseCashWalletId').value = walletId;
    document.getElementById('expenseModalSubtitle').textContent = 'Cartera: ' + walletName;
    document.getElementById('expenseForm').action = '{{ url("/cash-wallets") }}/' + walletId + '/expense';
    document.getElementById('expenseModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeExpenseModal() {
    document.getElementById('expenseModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('expenseForm').reset();
}

// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('expenseModal').classList.contains('hidden')) {
        closeExpenseModal();
    }
    if (e.key === 'Escape' && !document.getElementById('transferModal').classList.contains('hidden')) {
        closeTransferModal();
    }
});
</script>

<!-- Modal para ingresar en banco -->
<div id="transferModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeTransferModal()"></div>
        
        <!-- Modal panel -->
        <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all w-full max-w-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900" id="transferModalTitle">Ingresar en Banco</h3>
                    <p class="text-sm text-slate-500" id="transferModalSubtitle"></p>
                </div>
                <button type="button" onclick="closeTransferModal()" class="rounded-xl border border-slate-200 bg-white p-2 text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <form id="transferForm" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="cash_wallet_id" id="transferCashWalletId">
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                            <input type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Tienda destino *</span>
                            <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona una tienda</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                            <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="0.00"/>
                        </label>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" onclick="closeTransferModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTransferModal(walletId, walletName) {
    document.getElementById('transferCashWalletId').value = walletId;
    document.getElementById('transferModalSubtitle').textContent = 'Cartera: ' + walletName;
    document.getElementById('transferForm').action = '{{ url("/cash-wallets") }}/' + walletId + '/transfer';
    document.getElementById('transferModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('transferForm').reset();
}
</script>
@endsection
