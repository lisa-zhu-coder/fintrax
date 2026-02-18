@extends('layouts.app')

@section('title', 'Control de Efectivo - ' . $store->name . ' - ' . $monthLabel)

@section('content')
@php
    $cashControlReturnUrl = route('financial.cash-control-month', ['store' => $store->id, 'month' => $monthKey] + request()->only(['period', 'date_from', 'date_to']));
@endphp
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('financial.cash-control-store', ['store' => $store->id] + request()->except(['month'])) }}" class="text-brand-600 hover:text-brand-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-lg font-semibold">Control de Efectivo - {{ $store->name }} - {{ $monthLabel }}</h1>
                </div>
                <p class="text-sm text-slate-500">Efectivo retirado en este mes</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-500">Saldo del mes</div>
                <div class="text-lg font-semibold {{ $monthTotal >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ number_format($monthTotal, 2, ',', '.') }} €
                </div>
            </div>
        </div>
    </header>

    <!-- Cuadro de resumen -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-md font-semibold text-slate-900 mb-4">Resumen del Mes</h2>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-500 mb-1">Total Efectivo Retirado</div>
                <div class="text-lg font-semibold {{ $monthTotal >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ number_format($monthTotal, 2, ',', '.') }} €
                </div>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-500 mb-1">Total Efectivo Real</div>
                <div class="text-lg font-semibold {{ $totalCashReal >= 0 ? 'text-emerald-700' : 'text-rose-700' }}" data-summary="total-cash-real">
                    {{ number_format($totalCashReal, 2, ',', '.') }} €
                </div>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-500 mb-1">Total Efectivo Recogido</div>
                <div class="text-lg font-semibold text-amber-700">
                    {{ number_format($totalCashCollected, 2, ',', '.') }} €
                </div>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-500 mb-1">Total Gastos del Mes</div>
                <div class="text-lg font-semibold text-rose-700">
                    {{ number_format($monthExpensesTotal, 2, ',', '.') }} €
                </div>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-500 mb-1">Traspasos de efectivo</div>
                <div class="text-lg font-semibold {{ ($totalTraspasosEfectivo ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}" data-summary="total-traspasos-efectivo">
                    {{ number_format($totalTraspasosEfectivo ?? 0, 2, ',', '.') }} €
                </div>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-500 mb-1">Saldo del Mes</div>
                <div class="text-lg font-semibold {{ $monthBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}" data-summary="month-balance">
                    {{ number_format($monthBalance, 2, ',', '.') }} €
                </div>
            </div>
        </div>
    </div>

    <!-- Cuadro de gastos del mes -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-md font-semibold text-slate-900">Gastos del mes</h2>
            @if(auth()->user()->hasPermission('treasury.cash_control.create'))
            <button type="button" onclick="document.getElementById('addExpenseModal').classList.remove('hidden')" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Añadir gasto
            </button>
            @endif
        </div>
        
        @if(empty($monthExpenses))
            <div class="py-4 text-center text-slate-500 text-sm">
                No hay gastos del mes
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Fecha</th>
                            <th class="px-3 py-2 text-left">Categoría</th>
                            <th class="px-3 py-2 text-left">Concepto</th>
                            <th class="px-3 py-2 text-right">Importe</th>
                            <th class="px-3 py-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($monthExpenses as $expense)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-slate-700">{{ $expense['date'] }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ ucfirst(str_replace('_', ' ', $expense['category'])) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-slate-700">{{ $expense['concept'] }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-rose-700">
                                    {{ number_format($expense['amount'], 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('financial.show', [$expense['id'], 'return_to' => $cashControlReturnUrl]) }}" class="text-brand-600 hover:text-brand-700 text-xs">
                                        Ver
                                    </a>
                                    @if(auth()->user()->hasPermission('financial.expenses.delete') || auth()->user()->hasPermission('financial.registros.delete'))
                                    <form method="POST" action="{{ route('financial.destroy', $expense['id']) }}" class="inline ml-1" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este gasto?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ $cashControlReturnUrl }}">
                                        <button type="submit" class="text-rose-600 hover:text-rose-700 text-xs font-medium">
                                            Eliminar
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50 font-semibold">
                            <td colspan="3" class="px-3 py-2 text-right">Total:</td>
                            <td class="px-3 py-2 text-right text-rose-700">
                                {{ number_format($monthExpensesTotal, 2, ',', '.') }} €
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Historial de efectivos recogidos del mes -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-md font-semibold text-slate-900 mb-4">Historial de efectivos recogidos del mes</h2>
        @if($cashWithdrawals->isEmpty())
            <div class="py-4 text-center text-slate-500 text-sm">
                No hay efectivos recogidos en este mes
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Fecha</th>
                            <th class="px-3 py-2 text-left">Cartera/Monedero</th>
                            <th class="px-3 py-2 text-right">Importe</th>
                            <th class="px-3 py-2 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($cashWithdrawals as $withdrawal)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 text-slate-700">{{ $withdrawal->date->format('d/m/Y') }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $withdrawal->cashWallet->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-amber-700">
                                    {{ number_format($withdrawal->amount, 2, ',', '.') }} €
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('cash-wallets.withdrawals.edit', ['cashWallet' => $withdrawal->cash_wallet_id, 'withdrawal' => $withdrawal->id]) }}" 
                                            class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('cash-wallets.withdrawals.destroy', ['cashWallet' => $withdrawal->cash_wallet_id, 'withdrawal' => $withdrawal->id]) }}" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta recogida de efectivo? También se eliminará del historial de la cartera.')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="{{ route('financial.cash-control-month', ['store' => $store->id, 'month' => $monthKey] + request()->only(['period', 'date_from', 'date_to'])) }}">
                                            <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50 font-semibold">
                            <td colspan="2" class="px-3 py-2 text-right">Total:</td>
                            <td class="px-3 py-2 text-right text-amber-700">
                                {{ number_format($totalCashCollected, 2, ',', '.') }} €
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Tabla de entradas de efectivo retirado -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-md font-semibold text-slate-900 mb-4">Efectivo Retirado</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2 text-right">Efectivo Retirado</th>
                        <th class="px-3 py-2 text-right">Gastos</th>
                        <th class="px-3 py-2 text-right">Efectivo Esperado</th>
                        <th class="px-3 py-2 text-right">Efectivo Real</th>
                        <th class="px-3 py-2 text-right">Discrepancia</th>
                        <th class="px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50" data-entry-id="{{ $entry->id }}">
                            <td class="px-3 py-2">{{ $entry->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $entry->cash_withdrawn >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ number_format($entry->cash_withdrawn, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-rose-700">
                                {{ number_format($entry->day_expenses_total, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-blue-700">
                                {{ number_format($entry->expected_cash, 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-right">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    value="{{ $entry->cash_real !== null ? number_format($entry->cash_real, 2, '.', '') : '' }}" 
                                    placeholder="0.00"
                                    data-entry-id="{{ $entry->id }}"
                                    data-expected-cash="{{ $entry->expected_cash }}"
                                    class="cash-real-input w-24 rounded-lg border border-slate-200 px-2 py-1 text-sm text-right outline-none ring-brand-200 focus:ring-2"
                                    onchange="updateCashReal({{ $entry->id }}, this.value, {{ $entry->expected_cash }})"
                                />
                            </td>
                            <td class="px-3 py-2 text-right font-semibold discrepancy-cell" 
                                data-entry-id="{{ $entry->id }}"
                                data-expected-cash="{{ $entry->expected_cash }}">
                                @php
                                    $discrepancy = ($entry->cash_real !== null) ? ($entry->cash_real - $entry->expected_cash) : null;
                                @endphp
                                @if($discrepancy !== null)
                                    <span class="{{ $discrepancy >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($discrepancy, 2, ',', '.') }} €
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('financial.show', [$entry->id, 'return_to' => $cashControlReturnUrl]) }}" class="text-brand-600 hover:text-brand-700 text-xs" title="Ver detalles">
                                    Ver
                                </a>
                            </td>
                        </tr>
                        @if(isset($expensesByDay[$entry->date->format('Y-m-d')]) && !empty($expensesByDay[$entry->date->format('Y-m-d')]))
                            <tr class="bg-slate-50">
                                <td colspan="7" class="px-3 py-2">
                                    <div class="ml-4">
                                        <div class="text-xs font-semibold text-slate-600 mb-2">Gastos del día:</div>
                                        <div class="space-y-1">
                                            @foreach($expensesByDay[$entry->date->format('Y-m-d')] as $dayExpense)
                                                <div class="flex items-center justify-between text-xs">
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">
                                                            {{ ucfirst(str_replace('_', ' ', $dayExpense['category'])) }}
                                                        </span>
                                                        <span class="text-slate-700">{{ $dayExpense['concept'] }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="font-semibold text-rose-700">
                                                            {{ number_format($dayExpense['amount'], 2, ',', '.') }} €
                                                        </span>
                                                        <a href="{{ route('financial.show', [$dayExpense['id'], 'return_to' => $cashControlReturnUrl]) }}" class="text-brand-600 hover:text-brand-700">
                                                            Ver
                                                        </a>
                                                        @if(auth()->user()->hasPermission('financial.expenses.delete') || auth()->user()->hasPermission('financial.registros.delete'))
                                                        <form method="POST" action="{{ route('financial.destroy', $dayExpense['id']) }}" class="inline ml-1" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este gasto?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="redirect_to" value="{{ $cashControlReturnUrl }}">
                                                            <button type="submit" class="text-rose-600 hover:text-rose-700 text-xs font-medium">Eliminar</button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">No hay registros para este mes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para añadir gasto -->
<div id="addExpenseModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Añadir Gasto</h3>
                <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="{{ route('financial.cash-control-expense', ['store' => $store->id, 'month' => $monthKey] + request()->only(['period', 'date_from', 'date_to'])) }}">
                @csrf
                
                <div class="space-y-4">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Fecha de procedencia *</span>
                        <select name="procedence_date" id="procedenceDate" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Selecciona...</option>
                            <option value="{{ $monthKey }}">{{ $monthLabel }} (Mes completo)</option>
                            @foreach($days as $day)
                                <option value="{{ $day['value'] }}">{{ $day['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Proveedor</span>
                        <select name="supplier_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                            <option value="">Ninguno</option>
                            @foreach($suppliers ?? [] as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Categoría *</span>
                        <select name="expense_category" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
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
                        <input type="text" name="expense_concept" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                    
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                        <input type="number" name="expense_amount" step="0.01" min="0" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                </div>
                
                <div class="mt-6 flex items-center gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="fixed bottom-4 right-4 bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-lg z-50">
        {{ session('success') }}
    </div>
    <script>
        setTimeout(() => {
            document.querySelector('.fixed.bottom-4').remove();
        }, 3000);
    </script>
@endif

<script>
function updateCashReal(entryId, value, expectedCash) {
    // Limpiar el valor y parsearlo correctamente
    const cleanValue = value ? value.toString().trim() : '';
    const cashReal = cleanValue !== '' && !isNaN(parseFloat(cleanValue)) ? parseFloat(cleanValue) : null;
    
    const formData = new FormData();
    
    if (cashReal !== null && !isNaN(cashReal)) {
        formData.append('cash_real', cashReal);
    } else {
        formData.append('cash_real', '');
    }
    
    fetch(`{{ route('financial.update-cash-real', ['entry' => '__ENTRY_ID__']) }}`.replace('__ENTRY_ID__', entryId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Error en la respuesta del servidor');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // El efectivo real se ha guardado correctamente
            console.log('Efectivo real actualizado:', data.cash_real);
            
            // Actualizar la discrepancia
            const discrepancyCell = document.querySelector(`.discrepancy-cell[data-entry-id="${entryId}"]`);
            if (discrepancyCell) {
                const expectedCash = parseFloat(discrepancyCell.getAttribute('data-expected-cash'));
                const cashReal = data.cash_real !== null ? parseFloat(data.cash_real) : null;
                
                if (cashReal !== null && !isNaN(cashReal) && !isNaN(expectedCash)) {
                    const discrepancy = cashReal - expectedCash;
                    const formattedDiscrepancy = discrepancy.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    discrepancyCell.innerHTML = `<span class="${discrepancy >= 0 ? 'text-emerald-700' : 'text-rose-700'}">${formattedDiscrepancy} €</span>`;
                } else {
                    discrepancyCell.innerHTML = '<span class="text-slate-400">-</span>';
                }
            }
            
            // Actualizar el resumen
            updateSummary();
        } else {
            console.error('Error en la respuesta:', data);
            alert('Error al actualizar el efectivo real: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el efectivo real: ' + error.message);
    });
}

function updateSummary() {
    // Calcular total efectivo real sumando todos los valores de los inputs
    let totalCashReal = 0;
    document.querySelectorAll('.cash-real-input').forEach(input => {
        const value = parseFloat(input.value);
        if (!isNaN(value) && value > 0) {
            totalCashReal += value;
        }
    });
    
    // Obtener total de gastos del mes, efectivo recogido y traspasos de efectivo
    const monthExpensesTotal = {{ $monthExpensesTotal }};
    const totalCashCollected = {{ $totalCashCollected }};
    const totalTraspasosEfectivo = {{ $totalTraspasosEfectivo ?? 0 }};
    
    // Saldo del mes = efectivo real - gastos del mes - efectivo recogido + traspasos de efectivo
    const monthBalance = totalCashReal - monthExpensesTotal - totalCashCollected + totalTraspasosEfectivo;
    
    // Actualizar los valores en el resumen
    const totalCashRealElement = document.querySelector('[data-summary="total-cash-real"]');
    const monthBalanceElement = document.querySelector('[data-summary="month-balance"]');
    
    if (totalCashRealElement) {
        const formatted = totalCashReal.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        totalCashRealElement.textContent = formatted + ' €';
        totalCashRealElement.className = `text-lg font-semibold ${totalCashReal >= 0 ? 'text-emerald-700' : 'text-rose-700'}`;
    }
    
    if (monthBalanceElement) {
        const formatted = monthBalance.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        monthBalanceElement.textContent = formatted + ' €';
        monthBalanceElement.className = `text-lg font-semibold ${monthBalance >= 0 ? 'text-emerald-700' : 'text-rose-700'}`;
    }
}

// Limpiar el 0 cuando se hace focus en campos de efectivo real + Enter pasa al siguiente
document.addEventListener('DOMContentLoaded', function() {
    const cashRealInputs = Array.from(document.querySelectorAll('.cash-real-input'));
    cashRealInputs.forEach(function(input, index) {
        input.addEventListener('focus', function() {
            if (parseFloat(this.value) === 0) {
                this.value = '';
            }
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const next = cashRealInputs[index + 1];
                if (next) {
                    next.focus();
                }
            }
            if (parseFloat(this.value) === 0 && /[0-9]/.test(e.key)) {
                this.value = '';
            }
        });
    });
});
</script>
@endsection
