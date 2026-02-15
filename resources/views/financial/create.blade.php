@extends('layouts.app')

@section('title', 'Añadir Registro Financiero')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Añadir Registro Financiero</h1>
                <p class="text-sm text-slate-500">Crea un nuevo registro de ingreso, gasto o cierre diario</p>
            </div>
            <a href="{{ route('financial.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Volver
            </a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('financial.store') }}" class="space-y-6" id="financialForm">
            @csrf
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', request('date', now()->format('Y-m-d'))) }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('date') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                    <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona una tienda</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id', request('store_id')) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block" @if(count($allowedTypes) === 1) style="display: none;" @endif>
                    <span class="text-xs font-semibold text-slate-700">Tipo *</span>
                    <select name="type" id="entryType" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @if(count($allowedTypes) > 1)
                            <option value="">Selecciona un tipo</option>
                        @endif
                        @if(in_array('daily_close', $allowedTypes))
                        <option value="daily_close" {{ old('type', $type) === 'daily_close' ? 'selected' : '' }}>Cierre diario</option>
                        @endif
                        @if(in_array('expense', $allowedTypes))
                        <option value="expense" {{ old('type', $type) === 'expense' ? 'selected' : '' }}>Gasto</option>
                        @endif
                        @if(in_array('income', $allowedTypes))
                        <option value="income" {{ old('type', $type) === 'income' ? 'selected' : '' }}>Ingreso</option>
                        @endif
                        @if(in_array('expense_refund', $allowedTypes))
                        <option value="expense_refund" {{ old('type', $type) === 'expense_refund' ? 'selected' : '' }}>Devolución de gasto</option>
                        @endif
                    </select>
                </label>
                @if(count($allowedTypes) === 1)
                    <input type="hidden" name="type" value="{{ $allowedTypes[0] }}">
                @endif

                <label class="block" id="conceptLabel" style="display: none;">
                    <span class="text-xs font-semibold text-slate-700">Concepto</span>
                    <input type="text" name="concept" id="concept" value="{{ old('concept') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>

                <label class="block" id="amountLabel" style="display: none;">
                    <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" value="{{ old('amount') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>

            <!-- Sección de Cierre Diario -->
            <div id="sectionDailyClose" class="hidden space-y-4">
                <!-- Conteo de caja -->
                <div class="rounded-xl border-2 border-brand-100 bg-brand-50/30 p-4 ring-1 ring-brand-100">
                    <div class="mb-3 flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-brand-700">
                            <path d="M21 4H3a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 8h10M7 12h10M7 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="text-sm font-semibold text-brand-900">Conteo de caja</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Efectivo inicial (€) *</span>
                            <input type="number" name="cash_initial" id="cashInitial" step="0.01" min="0" value="{{ old('cash_initial', 0) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Tarjeta (€) *</span>
                            <input type="number" name="tpv" id="tpv" step="0.01" min="0" value="{{ old('tpv', 0) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Gastos en efectivo (€)</span>
                            <input type="number" name="cash_expenses" id="cashExpenses" step="0.01" value="{{ old('cash_expenses', 0) }}" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                            <div class="mt-1 text-xs text-slate-500">Se calcula automáticamente sumando los gastos del cierre.</div>
                        </label>
                    </div>

                    <!-- Conteo de monedas y billetes -->
                    <div class="mt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700">Conteo de efectivo</span>
                            <div class="text-xs text-slate-600">
                                Total contado: <span id="cashCountTotal" class="font-semibold text-brand-700">0,00 €</span>
                            </div>
                        </div>
                        <div id="cashCountContainer" class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                            <!-- Se llenará con JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Gastos (dentro del cierre) -->
                <div class="rounded-xl border-2 border-rose-100 bg-rose-50/30 p-4 ring-1 ring-rose-100">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-rose-700">
                                <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-sm font-semibold text-rose-900">Gastos (efectivo)</span>
                        </div>
                        <button type="button" id="addExpenseItemBtn" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                            <span>+ Añadir gasto</span>
                        </button>
                    </div>

                    <div class="text-xs text-slate-600">Añade uno o varios gastos (puedes usar valores negativos, p. ej. para devoluciones). La suma será el “Gastos en efectivo”.</div>

                    <div id="expenseItemsContainer" class="mt-3 space-y-2">
                        @php
                            $existingExpenseItems = old('expense_items', []);
                        @endphp
                        @foreach($existingExpenseItems as $i => $item)
                            @php $idx = is_numeric($i) ? (int) $i : $loop->index; @endphp
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2" data-expense-index="{{ $idx }}">
                                <input
                                    type="text"
                                    name="expense_items[{{ $idx }}][concept]"
                                    value="{{ $item['concept'] ?? '' }}"
                                    placeholder="Concepto"
                                    required
                                    class="flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"
                                />
                                <input
                                    type="number"
                                    name="expense_items[{{ $idx }}][amount]"
                                    value="{{ $item['amount'] ?? '' }}"
                                    step="0.01"
                                    placeholder="0.00"
                                    required
                                    class="w-28 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"
                                />
                                <button type="button" onclick="this.closest('div[data-expense-index]').remove(); updateDailyCloseTotals();" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" aria-label="Eliminar gasto">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex items-center justify-between rounded-lg bg-white p-2 ring-1 ring-rose-100">
                        <span class="text-xs font-semibold text-slate-700">Total gastos</span>
                        <span id="totalExpenses" class="text-sm font-semibold text-rose-900">0,00 €</span>
                    </div>
                </div>

                <!-- Vales (visible solo si está activado en ajustes) -->
                @php $vouchersEnabled = $dailyCloseSettings['vouchers_enabled'] ?? true; @endphp
                @if($vouchersEnabled)
                <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
                    <div class="mb-3 flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-emerald-700">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="text-sm font-semibold text-emerald-900">Vales</span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Vales entrada (€)</span>
                            <input type="number" name="vouchers_in" id="vouchersIn" step="0.01" min="0" value="{{ old('vouchers_in', 0) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Vales salida (€)</span>
                            <input type="number" name="vouchers_out" id="vouchersOut" step="0.01" min="0" value="{{ old('vouchers_out', 0) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Resultado vales (€)</span>
                            <input type="number" name="vouchers_result" id="vouchersResult" step="0.01" value="{{ old('vouchers_result', 0) }}" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"/>
                        </label>
                    </div>
                </div>
                @else
                <input type="hidden" name="vouchers_in" value="0">
                <input type="hidden" name="vouchers_out" value="0">
                <input type="hidden" name="vouchers_result" value="0">
                @endif

                <!-- Sistema POS (nombres configurables en Ajustes > Cierre de caja) -->
                @php
                    $posLabel = $dailyCloseSettings['pos_label'] ?? 'Sistema POS';
                    $posCashLabel = $dailyCloseSettings['pos_cash_label'] ?? 'Sistema POS · Efectivo (€)';
                    $posCardLabel = $dailyCloseSettings['pos_card_label'] ?? 'Sistema POS · Tarjeta (€)';
                @endphp
                <div class="rounded-xl border-2 border-blue-100 bg-blue-50/30 p-4 ring-1 ring-blue-100">
                    <div class="mb-3 flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-blue-700">
                            <path d="M12 2L2 7l10 5 10-5-10-5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="text-sm font-semibold text-blue-900">{{ $posLabel }}</span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">{{ $posCashLabel }}</span>
                            <input type="number" name="shopify_cash" id="shopifyCash" step="0.01" min="0" value="{{ old('shopify_cash') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">{{ $posCardLabel }}</span>
                            <input type="number" name="shopify_tpv" id="shopifyTpv" step="0.01" min="0" value="{{ old('shopify_tpv') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                    </div>
                </div>

                <!-- Resultado -->
                <div class="rounded-xl border-2 border-slate-200 bg-slate-50 p-4 ring-1 ring-slate-200">
                    <div class="mb-3 flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-700">
                            <path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="text-sm font-semibold text-slate-900">Resultado</span>
                    </div>
                    <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                        <div class="text-xs font-semibold text-slate-700">Ventas totales (€)</div>
                        <div id="totalSales" class="mt-1 text-lg font-semibold text-slate-900">0,00 €</div>
                        <div class="mt-2 space-y-1 text-xs text-slate-600">
                            <div>Efectivo: <span id="computedCashSales" class="font-semibold text-slate-900">0,00 €</span></div>
                            <div>Datáfono: <span id="computedTpvSales" class="font-semibold text-slate-900">0,00 €</span></div>
                            @if($vouchersEnabled)
                            <div>Vales: <span id="computedVouchersSales" class="font-semibold text-slate-900">0,00 €</span></div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 rounded-xl bg-white p-3 ring-1 ring-slate-100">
                        <div class="text-xs font-semibold text-slate-700">Gastos totales (€)</div>
                        <div id="totalExpensesDisplay" class="mt-1 text-lg font-semibold text-slate-900">0,00 €</div>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                            <div class="text-xs font-semibold text-slate-700">Discrepancia (efectivo vs Shopify)</div>
                            <div id="cashDiscrepancy" class="mt-1 text-sm font-semibold">—</div>
                        </div>
                        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                            <div class="text-xs font-semibold text-slate-700">Discrepancia (tarjeta vs Shopify)</div>
                            <div id="tpvDiscrepancy" class="mt-1 text-sm font-semibold">—</div>
                        </div>
                        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                            <div class="text-xs font-semibold text-slate-700">Retirar efectivo</div>
                            <div id="withdrawAmount" class="mt-1 text-sm font-semibold text-slate-900">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Gasto simple -->
            <div id="sectionExpense" class="hidden space-y-4">
                <div class="rounded-xl border-2 border-rose-100 bg-rose-50/30 p-4 ring-1 ring-rose-100">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Proveedor</span>
                            <select name="supplier_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Ninguno</option>
                                @foreach($suppliers ?? [] as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Categoría del gasto</span>
                            <select name="expense_category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="alquiler" {{ old('expense_category') === 'alquiler' ? 'selected' : '' }}>Alquiler</option>
                                <option value="impuestos" {{ old('expense_category') === 'impuestos' ? 'selected' : '' }}>Impuestos</option>
                                <option value="seguridad_social" {{ old('expense_category') === 'seguridad_social' ? 'selected' : '' }}>Seguridad Social</option>
                                <option value="suministros" {{ old('expense_category') === 'suministros' ? 'selected' : '' }}>Suministros</option>
                                <option value="servicios_profesionales" {{ old('expense_category') === 'servicios_profesionales' ? 'selected' : '' }}>Servicios profesionales</option>
                                <option value="sueldos" {{ old('expense_category') === 'sueldos' ? 'selected' : '' }}>Sueldos</option>
                                <option value="miramira" {{ old('expense_category') === 'miramira' ? 'selected' : '' }}>Miramira</option>
                                <option value="mercaderia" {{ old('expense_category') === 'mercaderia' ? 'selected' : '' }}>Mercadería</option>
                                <option value="equipamiento" {{ old('expense_category') === 'equipamiento' ? 'selected' : '' }}>Equipamiento</option>
                                <option value="otros" {{ old('expense_category') === 'otros' ? 'selected' : '' }}>Otros</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Método de pago</span>
                            <select name="expense_payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="cash" {{ old('expense_payment_method') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                <option value="bank" {{ old('expense_payment_method') === 'bank' ? 'selected' : '' }}>Banco</option>
                                <option value="card" {{ old('expense_payment_method') === 'card' ? 'selected' : '' }}>Tarjeta</option>
                            </select>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-xs font-semibold text-slate-700">Concepto del gasto</span>
                            <input type="text" name="expense_concept" value="{{ old('expense_concept') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Importe total (€) *</span>
                            <input type="number" name="total_amount" id="totalAmount" step="0.01" min="0" value="{{ old('total_amount', old('expense_amount')) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Estado</span>
                            <select name="status" id="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="pendiente" {{ old('status', 'pendiente') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="pagado" {{ old('status') === 'pagado' ? 'selected' : '' }}>Pagado</option>
                            </select>
                        </label>
                    </div>
                </div>

                <!-- Sección de Pagos -->
                <div class="rounded-xl border-2 border-blue-100 bg-blue-50/30 p-4 ring-1 ring-blue-100">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-blue-700">
                                <path d="M21 4H3a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 8h10M7 12h10M7 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span class="text-sm font-semibold text-blue-900">Pagos</span>
                        </div>
                        <button type="button" id="addExpensePaymentBtn" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                            <span>+ Añadir pago</span>
                        </button>
                    </div>

                    <div id="expensePaymentsContainer" class="space-y-2">
                        @php
                            $existingPayments = old('expense_payments', []);
                        @endphp
                        @foreach($existingPayments as $i => $payment)
                            @php $idx = is_numeric($i) ? (int) $i : $loop->index; @endphp
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2" data-payment-index="{{ $idx }}">
                                <input type="date" name="expense_payments[{{ $idx }}][date]" value="{{ $payment['date'] ?? '' }}" required class="w-40 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
                                <select name="expense_payments[{{ $idx }}][method]" required class="w-32 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2">
                                    <option value="">Método</option>
                                    <option value="cash" {{ ($payment['method'] ?? '') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                    <option value="bank" {{ ($payment['method'] ?? '') === 'bank' ? 'selected' : '' }}>Banco</option>
                                </select>
                                <input type="number" name="expense_payments[{{ $idx }}][amount]" step="0.01" min="0" value="{{ $payment['amount'] ?? '' }}" placeholder="0.00" required class="flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
                                <button type="button" onclick="this.closest('div[data-payment-index]').remove(); updateExpensePaymentsTotal();" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" aria-label="Eliminar pago">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex items-center justify-between rounded-lg bg-white p-2 ring-1 ring-blue-100">
                        <span class="text-xs font-semibold text-slate-700">Total pagado</span>
                        <span id="expensePaymentsTotal" class="text-sm font-semibold text-blue-900">0,00 €</span>
                    </div>
                </div>
                    </div>
                </div>
                
                <!-- División entre tiendas -->
                <div class="rounded-xl border border-slate-200 bg-white p-4 ring-1 ring-slate-100">
                    <label class="flex items-center gap-2 mb-4">
                        <input type="checkbox" id="expenseSplitStores" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500"/>
                        <span class="text-sm font-semibold text-slate-700">Dividir este gasto entre tiendas</span>
                    </label>
                    <div id="expenseSplitContainer" class="hidden space-y-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs font-semibold text-slate-700 mb-2">Selecciona las tiendas que participan:</div>
                            <div id="expenseSplitStoreCheckboxes" class="space-y-2">
                                @foreach($stores as $store)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="expense_split_stores[]" value="{{ $store->id }}" class="expense-split-store-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500"/>
                                    <span class="text-sm text-slate-700">{{ $store->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Distribución por tienda:</span>
                            <span class="text-slate-600">
                                Total: <span id="expenseSplitTotal" class="font-semibold text-brand-700">0,00 €</span>
                            </span>
                        </div>
                        <div id="expenseSplitStoresList" class="space-y-2">
                            <!-- Se llena dinámicamente -->
                        </div>
                        <div id="expenseSplitError" class="hidden rounded-lg bg-rose-50 p-2 text-xs text-rose-700 ring-1 ring-rose-200">
                            La suma de las cantidades debe ser igual al importe total del gasto.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Ingreso -->
            <div id="sectionIncome" class="hidden space-y-4">
                <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Categoría del ingreso</span>
                            <select name="income_category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="servicios_financieros" {{ old('income_category') === 'servicios_financieros' ? 'selected' : '' }}>Servicios financieros</option>
                                <option value="ventas" {{ old('income_category') === 'ventas' ? 'selected' : '' }}>Ventas</option>
                                <option value="otros" {{ old('income_category') === 'otros' ? 'selected' : '' }}>Otros</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Concepto del ingreso</span>
                            <input type="text" name="income_concept" value="{{ old('income_concept') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Método de pago</span>
                            <select name="expense_payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="cash" {{ old('expense_payment_method') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                <option value="bank" {{ old('expense_payment_method') === 'bank' ? 'selected' : '' }}>Banco</option>
                                <option value="card" {{ old('expense_payment_method') === 'card' ? 'selected' : '' }}>Tarjeta</option>
                                <option value="datafono" {{ old('expense_payment_method') === 'datafono' ? 'selected' : '' }}>Datáfono</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sección: Devolución -->
            <div id="sectionRefund" class="hidden space-y-4">
                <div class="rounded-xl border-2 border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-100">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Concepto de la devolución</span>
                        <input type="text" name="refund_concept" value="{{ old('refund_concept') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                </div>
            </div>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Notas</span>
                <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">{{ old('notes') }}</textarea>
            </label>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('financial.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Crear registro
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
const DENOMINATIONS = [
    { type: "billete", value: 500, label: "500 €" },
    { type: "billete", value: 200, label: "200 €" },
    { type: "billete", value: 100, label: "100 €" },
    { type: "billete", value: 50, label: "50 €" },
    { type: "billete", value: 20, label: "20 €" },
    { type: "billete", value: 10, label: "10 €" },
    { type: "billete", value: 5, label: "5 €" },
    { type: "moneda", value: 2, label: "2 €" },
    { type: "moneda", value: 1, label: "1 €" },
    { type: "moneda", value: 0.5, label: "0,50 €" },
    { type: "moneda", value: 0.2, label: "0,20 €" },
    { type: "moneda", value: 0.1, label: "0,10 €" },
    { type: "moneda", value: 0.05, label: "0,05 €" },
    { type: "moneda", value: 0.02, label: "0,02 €" },
    { type: "moneda", value: 0.01, label: "0,01 €" },
];

let expenseItemIndex = 0;

// Declarar variables globales para elementos del formulario (dentro de DOMContentLoaded)
const totalAmountInput = document.getElementById('totalAmount');

// Renderizar denominaciones
function renderCashCount() {
    const container = document.getElementById('cashCountContainer');
    if (!container) return;
    
    container.innerHTML = '';
    DENOMINATIONS.forEach(denom => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 ring-1 ring-slate-100';
        div.innerHTML = `
            <label class="flex-1">
                <span class="block text-xs font-medium text-slate-700">${denom.label}</span>
                <input
                    type="number"
                    min="0"
                    step="1"
                    data-denom="${denom.value}"
                    name="cash_count[${denom.value}]"
                    value="0"
                    placeholder="0"
                    class="cash-count-input mt-1 w-full rounded-lg border-0 bg-slate-50 px-2 py-1.5 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-400"
                />
            </label>
        `;
        container.appendChild(div);
    });
    
    container.querySelectorAll('.cash-count-input').forEach(input => {
        input.addEventListener('input', updateDailyCloseTotals);
    });
}

function updateCashCountTotal() {
    const container = document.getElementById('cashCountContainer');
    if (!container) return;
    
    let total = 0;
    container.querySelectorAll('.cash-count-input').forEach(input => {
        const denom = parseFloat(input.dataset.denom);
        const count = parseInt(input.value) || 0;
        total += denom * count;
    });
    
    document.getElementById('cashCountTotal').textContent = formatEuro(total);
}

function addExpenseItem() {
    const container = document.getElementById('expenseItemsContainer');
    if (!container) return;
    
    const index = expenseItemIndex++;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2';
    div.dataset.expenseIndex = index;
    div.innerHTML = `
        <input type="text" name="expense_items[${index}][concept]" placeholder="Concepto" required class="flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
        <input type="number" name="expense_items[${index}][amount]" step="0.01" placeholder="0.00" required class="w-24 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
        <button type="button" onclick="this.closest('div[data-expense-index]').remove(); updateDailyCloseTotals();" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    `;
    container.appendChild(div);
    
    div.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', updateDailyCloseTotals);
        
        // Aplicar limpieza de 0 a campos numéricos añadidos dinámicamente
        if (input.type === 'number' && !input.readOnly) {
            input.addEventListener('focus', function() {
                if (parseFloat(this.value) === 0) {
                    this.value = '';
                }
            });
            input.addEventListener('keydown', function(e) {
                if (parseFloat(this.value) === 0 && /[0-9]/.test(e.key)) {
                    this.value = '';
                }
            });
        }
    });
}

function updateDailyCloseTotals() {
    const type = document.getElementById('entryType').value;
    if (type !== 'daily_close') return;
    
    // Calcular total de gastos
    let totalExpenses = 0;
    document.querySelectorAll('[name^="expense_items"][name$="[amount]"]').forEach(input => {
        totalExpenses += parseFloat(input.value) || 0;
    });
    const totalExpensesEl = document.getElementById('totalExpenses');
    if (totalExpensesEl) totalExpensesEl.textContent = formatEuro(totalExpenses);
    document.getElementById('totalExpensesDisplay').textContent = formatEuro(totalExpenses);
    document.getElementById('cashExpenses').value = totalExpenses.toFixed(2);
    
    // Calcular efectivo contado
    updateCashCountTotal();
    const cashCounted = calculateCashTotal();
    const cashInitial = parseFloat(document.getElementById('cashInitial').value) || 0;
    const cashExpenses = totalExpenses;
    const computedCashSales = cashCounted - cashInitial + cashExpenses;

    // Retirar efectivo = efectivo total contado - efectivo inicial
    const withdraw = cashCounted - cashInitial;
    const withdrawEl = document.getElementById('withdrawAmount');
    if (withdrawEl) withdrawEl.textContent = formatEuro(withdraw);
    
    // Calcular vales (pueden no existir si la sección vales está desactivada en ajustes)
    const vouchersInEl = document.getElementById('vouchersIn');
    const vouchersOutEl = document.getElementById('vouchersOut');
    const vouchersIn = vouchersInEl ? (parseFloat(vouchersInEl.value) || 0) : 0;
    const vouchersOut = vouchersOutEl ? (parseFloat(vouchersOutEl.value) || 0) : 0;
    const vouchersResult = vouchersIn - vouchersOut;
    const vouchersResultEl = document.getElementById('vouchersResult');
    if (vouchersResultEl) vouchersResultEl.value = vouchersResult.toFixed(2);
    
    // Calcular ventas totales
    const tpv = parseFloat(document.getElementById('tpv').value) || 0;
    const totalSales = tpv + computedCashSales + vouchersResult;
    
    document.getElementById('computedCashSales').textContent = formatEuro(computedCashSales);
    document.getElementById('computedTpvSales').textContent = formatEuro(tpv);
    const computedVouchersEl = document.getElementById('computedVouchersSales');
    if (computedVouchersEl) computedVouchersEl.textContent = formatEuro(vouchersResult);
    document.getElementById('totalSales').textContent = formatEuro(totalSales);
    
    // Actualizar campos ocultos
    document.getElementById('amount').value = totalSales.toFixed(2);
    
    // Discrepancias
    const shopifyCash = parseFloat(document.getElementById('shopifyCash').value);
    const shopifyTpv = parseFloat(document.getElementById('shopifyTpv').value);
    
    if (shopifyCash !== null && !isNaN(shopifyCash)) {
        const discrepancy = computedCashSales - shopifyCash;
        const discEl = document.getElementById('cashDiscrepancy');
        discEl.textContent = formatEuro(discrepancy);
        discEl.className = discrepancy === 0 ? 'mt-1 text-sm font-semibold text-emerald-700' : 'mt-1 text-sm font-semibold text-amber-700';
    } else {
        document.getElementById('cashDiscrepancy').textContent = '—';
    }
    
    if (shopifyTpv !== null && !isNaN(shopifyTpv)) {
        const discrepancy = tpv - shopifyTpv;
        const discEl = document.getElementById('tpvDiscrepancy');
        discEl.textContent = formatEuro(discrepancy);
        discEl.className = discrepancy === 0 ? 'mt-1 text-sm font-semibold text-emerald-700' : 'mt-1 text-sm font-semibold text-amber-700';
    } else {
        document.getElementById('tpvDiscrepancy').textContent = '—';
    }
}

function calculateCashTotal() {
    const container = document.getElementById('cashCountContainer');
    if (!container) return 0;
    
    let total = 0;
    container.querySelectorAll('.cash-count-input').forEach(input => {
        const denom = parseFloat(input.dataset.denom);
        const count = parseInt(input.value) || 0;
        total += denom * count;
    });
    
    return round2(total);
}

function round2(num) {
    return Math.round(num * 100) / 100;
}

function formatEuro(amount) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

function toggleDailyCloseRequired(isDailyClose) {
    const cashInitial = document.getElementById('cashInitial');
    const tpv = document.getElementById('tpv');
    if (cashInitial) cashInitial.required = isDailyClose;
    if (tpv) tpv.required = isDailyClose;
}

// Mostrar/ocultar secciones según el tipo
const entryTypeElement = document.getElementById('entryType');
if (entryTypeElement) {
    entryTypeElement.addEventListener('change', function() {
        const type = this.value;
        document.getElementById('sectionDailyClose').classList.toggle('hidden', type !== 'daily_close');
        document.getElementById('sectionExpense').classList.toggle('hidden', type !== 'expense');
        document.getElementById('sectionIncome').classList.toggle('hidden', type !== 'income');
        document.getElementById('sectionRefund').classList.toggle('hidden', type !== 'expense_refund');
        
        // Ocultar/mostrar campos de concepto e importe según el tipo
        const conceptLabel = document.getElementById('conceptLabel');
        const amountLabel = document.getElementById('amountLabel');
        const conceptInput = document.getElementById('concept');
        const amountInput = document.getElementById('amount');
        
        if (type === 'daily_close' || type === 'expense') {
            // Ocultar concepto e importe para cierre diario y gastos
            if (conceptLabel) conceptLabel.style.display = 'none';
            if (amountLabel) amountLabel.style.display = 'none';
            if (conceptInput) conceptInput.removeAttribute('required');
            if (amountInput) amountInput.removeAttribute('required');
        } else {
            // Mostrar concepto e importe para otros tipos
            if (conceptLabel) conceptLabel.style.display = 'block';
            if (amountLabel) amountLabel.style.display = 'block';
            if (amountInput) amountInput.setAttribute('required', 'required');
        }
        
        toggleDailyCloseRequired(type === 'daily_close');
        
        // Hacer total_amount required solo cuando es expense
        if (totalAmountInput) {
            if (type === 'expense') {
                totalAmountInput.setAttribute('required', 'required');
            } else {
                totalAmountInput.removeAttribute('required');
            }
        }
        
        if (type === 'daily_close') {
            renderCashCount();
            updateDailyCloseTotals();
        }
    });
}

// Event listeners
document.getElementById('addExpenseItemBtn')?.addEventListener('click', addExpenseItem);
document.getElementById('cashInitial')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('tpv')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('vouchersIn')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('vouchersOut')?.addEventListener('input', updateDailyCloseTotals);
// Nota: si la sección vales está desactivada, no existen vouchersIn/vouchersOut
document.getElementById('shopifyCash')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('shopifyTpv')?.addEventListener('input', updateDailyCloseTotals);

// Inicializar según tipo - PRIMERO mostrar/ocultar secciones basándose en el tipo de PHP
const phpType = '{{ $type }}';
const allowedTypesArray = @json($allowedTypes);
let initialType = phpType || 'daily_close'; // Por defecto daily_close

// Si hay un selector de tipo, obtener su valor, sino usar el tipo de PHP
if (entryTypeElement) {
    // Establecer el valor del select si viene de PHP
    if (phpType && entryTypeElement.value !== phpType) {
        entryTypeElement.value = phpType;
    }
    initialType = entryTypeElement.value || phpType || 'daily_close';
} else {
    // Si no hay selector (porque solo hay un tipo permitido), usar el tipo de PHP o el permitido
    if (allowedTypesArray.length === 1) {
        initialType = allowedTypesArray[0];
    } else {
        initialType = phpType || 'daily_close';
    }
}

// Mostrar la sección correcta INMEDIATAMENTE según el tipo inicial
const sectionDailyClose = document.getElementById('sectionDailyClose');
const sectionExpense = document.getElementById('sectionExpense');
const sectionIncome = document.getElementById('sectionIncome');
const sectionRefund = document.getElementById('sectionRefund');

// Asegurarse de que todas las secciones existan antes de manipularlas
if (initialType === 'daily_close') {
    if (sectionDailyClose) {
        sectionDailyClose.classList.remove('hidden');
        sectionDailyClose.style.display = '';
    }
    if (sectionExpense) {
        sectionExpense.classList.add('hidden');
        sectionExpense.style.display = 'none';
    }
    if (sectionIncome) {
        sectionIncome.classList.add('hidden');
        sectionIncome.style.display = 'none';
    }
    if (sectionRefund) {
        sectionRefund.classList.add('hidden');
        sectionRefund.style.display = 'none';
    }
} else if (initialType === 'expense') {
    if (sectionDailyClose) {
        sectionDailyClose.classList.add('hidden');
        sectionDailyClose.style.display = 'none';
    }
    if (sectionExpense) {
        sectionExpense.classList.remove('hidden');
        sectionExpense.style.display = '';
    }
    if (sectionIncome) {
        sectionIncome.classList.add('hidden');
        sectionIncome.style.display = 'none';
    }
    if (sectionRefund) {
        sectionRefund.classList.add('hidden');
        sectionRefund.style.display = 'none';
    }
} else if (initialType === 'income') {
    if (sectionDailyClose) {
        sectionDailyClose.classList.add('hidden');
        sectionDailyClose.style.display = 'none';
    }
    if (sectionExpense) {
        sectionExpense.classList.add('hidden');
        sectionExpense.style.display = 'none';
    }
    if (sectionIncome) {
        sectionIncome.classList.remove('hidden');
        sectionIncome.style.display = '';
    }
    if (sectionRefund) {
        sectionRefund.classList.add('hidden');
        sectionRefund.style.display = 'none';
    }
} else if (initialType === 'expense_refund') {
    if (sectionDailyClose) {
        sectionDailyClose.classList.add('hidden');
        sectionDailyClose.style.display = 'none';
    }
    if (sectionExpense) {
        sectionExpense.classList.add('hidden');
        sectionExpense.style.display = 'none';
    }
    if (sectionIncome) {
        sectionIncome.classList.add('hidden');
        sectionIncome.style.display = 'none';
    }
    if (sectionRefund) {
        sectionRefund.classList.remove('hidden');
        sectionRefund.style.display = '';
    }
}

// Ocultar concepto e importe si es cierre diario o gasto al iniciar
if (allowedTypesArray.length === 1 && (allowedTypesArray[0] === 'daily_close' || allowedTypesArray[0] === 'expense')) {
    const conceptLabel = document.getElementById('conceptLabel');
    const amountLabel = document.getElementById('amountLabel');
    const conceptInput = document.getElementById('concept');
    const amountInput = document.getElementById('amount');
    if (conceptLabel) conceptLabel.style.display = 'none';
    if (amountLabel) amountLabel.style.display = 'none';
    if (conceptInput) conceptInput.removeAttribute('required');
    if (amountInput) amountInput.removeAttribute('required');
}

toggleDailyCloseRequired(initialType === 'daily_close');

// Inicializar el estado de total_amount según el tipo inicial
if (totalAmountInput) {
    if (initialType === 'expense') {
        totalAmountInput.setAttribute('required', 'required');
    } else {
        totalAmountInput.removeAttribute('required');
    }
}

if (initialType === 'daily_close') {
    renderCashCount();
    // Pequeño delay para asegurar que todo esté renderizado
    setTimeout(() => {
        updateDailyCloseTotals();
    }, 100);
}

// Inicializar listeners de gastos existentes (create)
document.querySelectorAll('#expenseItemsContainer input').forEach(input => {
    input.addEventListener('input', updateDailyCloseTotals);
});

// Asegurar que el selector de tienda esté habilitado
const storeSelect = document.querySelector('select[name="store_id"]');
if (storeSelect) {
    storeSelect.disabled = false;
    storeSelect.removeAttribute('readonly');
}

// Limpiar el 0 cuando se hace clic o focus en campos numéricos
function setupNumberFieldClearing() {
    const numberInputs = document.querySelectorAll('input[type="number"]:not([readonly])');
    numberInputs.forEach(input => {
        // Al hacer focus, si el valor es 0, limpiarlo
        input.addEventListener('focus', function() {
            if (parseFloat(this.value) === 0) {
                this.value = '';
            }
        });
        
        // Al empezar a escribir, si el valor es 0, limpiarlo
        input.addEventListener('keydown', function(e) {
            if (parseFloat(this.value) === 0 && /[0-9]/.test(e.key)) {
                this.value = '';
            }
        });
    });
}

// Inicializar limpieza de campos numéricos
setupNumberFieldClearing();

// Manejo de división de gastos entre tiendas
const expenseSplitStores = document.getElementById('expenseSplitStores');
const expenseSplitContainer = document.getElementById('expenseSplitContainer');
const expenseAmountInput = document.getElementById('expenseAmount');
const expenseSplitStoreCheckboxes = document.querySelectorAll('.expense-split-store-checkbox');
const expenseSplitStoresList = document.getElementById('expenseSplitStoresList');
const expenseSplitTotal = document.getElementById('expenseSplitTotal');
const expenseSplitError = document.getElementById('expenseSplitError');

const storesData = @json($stores);

function updateExpenseSplit() {
    if (!expenseSplitStores.checked) {
        expenseSplitContainer.classList.add('hidden');
        return;
    }
    
    expenseSplitContainer.classList.remove('hidden');
    
    const selectedStores = Array.from(expenseSplitStoreCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => {
            const store = storesData.find(s => s.id == cb.value);
            return { id: store.id, name: store.name };
        });
    
    if (selectedStores.length === 0) {
        expenseSplitStoresList.innerHTML = '<p class="text-xs text-slate-500">Selecciona al menos una tienda</p>';
        expenseSplitTotal.textContent = '0,00 €';
        return;
    }
    
    const totalAmount = parseFloat(expenseAmountInput.value) || 0;
    const amountPerStore = totalAmount / selectedStores.length;
    
    expenseSplitStoresList.innerHTML = selectedStores.map((store, index) => {
        const amount = amountPerStore.toFixed(2);
        return `
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
                <div class="flex-1">
                    <div class="text-xs font-semibold text-slate-700">${store.name}</div>
                </div>
                <div class="w-32">
                    <input 
                        type="number" 
                        name="expense_split_amounts[${store.id}]" 
                        step="0.01" 
                        min="0" 
                        value="${amount}"
                        class="expense-split-amount w-full rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm outline-none ring-brand-200 focus:ring-2"
                        data-store-id="${store.id}"
                    />
                </div>
                <div class="w-20 text-right text-xs font-semibold text-slate-600">
                    <span class="expense-split-percentage">${((amountPerStore / totalAmount) * 100).toFixed(1)}%</span>
                </div>
            </div>
        `;
    }).join('');
    
    // Añadir event listeners a los inputs de cantidad
    document.querySelectorAll('.expense-split-amount').forEach(input => {
        input.addEventListener('input', validateExpenseSplit);
    });
    
    updateExpenseSplitTotal();
}

function validateExpenseSplit() {
    const totalAmount = parseFloat(expenseAmountInput.value) || 0;
    const splitAmounts = Array.from(document.querySelectorAll('.expense-split-amount'))
        .map(input => parseFloat(input.value) || 0);
    const sum = splitAmounts.reduce((a, b) => a + b, 0);
    
    const difference = Math.abs(sum - totalAmount);
    const tolerance = 0.01; // Tolerancia de 1 céntimo
    
    if (difference > tolerance) {
        expenseSplitError.classList.remove('hidden');
    } else {
        expenseSplitError.classList.add('hidden');
    }
    
    updateExpenseSplitTotal();
}

function updateExpenseSplitTotal() {
    const splitAmounts = Array.from(document.querySelectorAll('.expense-split-amount'))
        .map(input => parseFloat(input.value) || 0);
    const sum = splitAmounts.reduce((a, b) => a + b, 0);
    
    expenseSplitTotal.textContent = sum.toFixed(2).replace('.', ',') + ' €';
    
    // Actualizar porcentajes
    const totalAmount = parseFloat(expenseAmountInput.value) || 0;
    if (totalAmount > 0) {
        document.querySelectorAll('.expense-split-amount').forEach(input => {
            const amount = parseFloat(input.value) || 0;
            const percentage = (amount / totalAmount * 100).toFixed(1);
            const percentageEl = input.closest('.flex').querySelector('.expense-split-percentage');
            if (percentageEl) {
                percentageEl.textContent = percentage + '%';
            }
        });
    }
}

if (expenseSplitStores) {
    expenseSplitStores.addEventListener('change', updateExpenseSplit);
}

if (expenseAmountInput) {
    expenseAmountInput.addEventListener('input', function() {
        if (expenseSplitStores && expenseSplitStores.checked) {
            updateExpenseSplit();
        }
    });
    
    // También actualizar cuando cambia el valor (por si se carga desde old())
    expenseAmountInput.addEventListener('change', function() {
        if (expenseSplitStores && expenseSplitStores.checked) {
            updateExpenseSplit();
        }
    });
}

if (expenseSplitStoreCheckboxes.length > 0) {
    expenseSplitStoreCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (expenseSplitStores && expenseSplitStores.checked) {
                updateExpenseSplit();
            }
        });
    });
}

// Prevenir que el formulario se envíe si la división no suma correctamente
// Prevenir que Enter envíe el formulario y mover al siguiente campo
const financialForm = document.getElementById('financialForm');

if (financialForm) {
    // Prevenir que el formulario se envíe si la división no suma correctamente
    financialForm.addEventListener('submit', function(e) {
        if (expenseSplitStores && expenseSplitStores.checked) {
            const totalAmount = parseFloat(expenseAmountInput.value) || 0;
            const splitAmounts = Array.from(document.querySelectorAll('.expense-split-amount'))
                .map(input => parseFloat(input.value) || 0);
            const sum = splitAmounts.reduce((a, b) => a + b, 0);
            
            const difference = Math.abs(sum - totalAmount);
            const tolerance = 0.01;
            
            if (difference > tolerance) {
                e.preventDefault();
                expenseSplitError.classList.remove('hidden');
                expenseSplitError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                alert('La suma de las cantidades debe ser igual al importe total del gasto.');
                return false;
            }
        }
    });

    // Prevenir que Enter envíe el formulario y mover al siguiente campo
    financialForm.addEventListener('keydown', function(e) {
        // Si se presiona Enter en un campo de entrada (no en textarea o botones)
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit' && e.target.type !== 'button') {
            e.preventDefault();
            
            // Obtener todos los campos editables del formulario
            const allFields = Array.from(
                financialForm.querySelectorAll(
                    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([readonly]):not([disabled]), ' +
                    'select:not([disabled]), ' +
                    'textarea:not([readonly]):not([disabled])'
                )
            );
            
            // Filtrar campos visibles
            const visibleFields = allFields.filter((field) => {
                const style = window.getComputedStyle(field);
                const parent = field.closest('.hidden');
                return style.display !== 'none' && style.visibility !== 'hidden' && !parent && !field.disabled && !field.readOnly;
            });
            
            // Encontrar el índice del campo actual
            const currentIndex = visibleFields.indexOf(e.target);
            if (currentIndex >= 0 && currentIndex < visibleFields.length - 1) {
                // Mover al siguiente campo
                visibleFields[currentIndex + 1].focus();
                visibleFields[currentIndex + 1].select();
            }
        }
    });
    
    // Prevenir que el formulario se envíe accidentalmente con Enter
    // El formulario solo se enviará cuando se haga clic explícitamente en el botón de guardar
    financialForm.addEventListener('submit', function(e) {
        const submitButton = document.querySelector('button[type="submit"]');
        const focusedElement = document.activeElement;
        
        // Verificar si el evento fue disparado por un clic en el botón
        const isButtonClick = e.submitter && e.submitter === submitButton;
        
        // Si el elemento con foco no es el botón de submit Y no fue un clic en el botón, prevenir el envío
        // Esto evita que Enter envíe el formulario accidentalmente
        if (!isButtonClick && focusedElement && focusedElement !== submitButton && focusedElement.type !== 'submit') {
            e.preventDefault();
            return false;
        }
    });
}

// Sincronizar campos de gastos
// expenseAmountInput ya está declarado arriba en la sección de división de gastos
// totalAmountInput ya está declarado arriba
const paidAmountInput = document.getElementById('paidAmount');
const paymentDateInput = document.getElementById('paymentDate');
const statusSelect = document.getElementById('status');

// Sincronizar total_amount con expense_amount cuando cambie expense_amount
if (expenseAmountInput && totalAmountInput) {
    expenseAmountInput.addEventListener('input', function() {
        if (!totalAmountInput.value || totalAmountInput.value === '0') {
            totalAmountInput.value = this.value;
        }
    });
}

// Actualizar estado automáticamente cuando se cambie paid_amount o payment_date
if (paidAmountInput && statusSelect && totalAmountInput) {
    function updateStatus() {
        const paidAmount = parseFloat(paidAmountInput.value) || 0;
        const totalAmount = parseFloat(totalAmountInput.value) || 0;
        
        if (paidAmount >= totalAmount && totalAmount > 0) {
            statusSelect.value = 'pagado';
            if (paymentDateInput && !paymentDateInput.value) {
                paymentDateInput.value = new Date().toISOString().split('T')[0];
            }
        } else if (paidAmount > 0) {
            statusSelect.value = 'pendiente';
        }
    }
    
    paidAmountInput.addEventListener('input', updateStatus);
    totalAmountInput.addEventListener('input', updateStatus);
}

if (paymentDateInput && statusSelect) {
    paymentDateInput.addEventListener('change', function() {
        if (this.value && statusSelect.value === 'pendiente') {
            const paidAmount = parseFloat(paidAmountInput?.value) || 0;
            const totalAmount = parseFloat(totalAmountInput?.value) || 0;
            if (paidAmount >= totalAmount && totalAmount > 0) {
                statusSelect.value = 'pagado';
            }
        }
    });
}

if (statusSelect && paymentDateInput) {
    statusSelect.addEventListener('change', function() {
        if (this.value === 'pagado' && !paymentDateInput.value) {
            paymentDateInput.value = new Date().toISOString().split('T')[0];
        }
    });
}

// Manejo de pagos de gastos
let expensePaymentIndex = document.querySelectorAll('#expensePaymentsContainer [data-payment-index]').length || 0;

function addExpensePayment() {
    const container = document.getElementById('expensePaymentsContainer');
    if (!container) return;
    
    const index = expensePaymentIndex++;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2';
    div.dataset.paymentIndex = index;
    div.innerHTML = `
        <input type="date" name="expense_payments[${index}][date]" required class="w-40 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
        <select name="expense_payments[${index}][method]" required class="w-32 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2">
            <option value="">Método</option>
            <option value="cash">Efectivo</option>
            <option value="bank">Banco</option>
        </select>
        <input type="number" name="expense_payments[${index}][amount]" step="0.01" min="0" placeholder="0.00" required class="expense-payment-amount flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
        <button type="button" onclick="this.closest('div[data-payment-index]').remove(); updateExpensePaymentsTotal();" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    `;
    container.appendChild(div);
    
    // Añadir listener para actualizar total
    div.querySelector('.expense-payment-amount').addEventListener('input', updateExpensePaymentsTotal);
    
    // Limpiar 0 al hacer focus
    div.querySelector('.expense-payment-amount').addEventListener('focus', function() {
        if (parseFloat(this.value) === 0) {
            this.value = '';
        }
    });
}

function updateExpensePaymentsTotal() {
    const container = document.getElementById('expensePaymentsContainer');
    const totalEl = document.getElementById('expensePaymentsTotal');
    if (!container || !totalEl) return;
    
    let total = 0;
    container.querySelectorAll('.expense-payment-amount').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    
    totalEl.textContent = formatEuro(total);
    
    // Actualizar estado automáticamente
    // Usar la variable global totalAmountInput declarada más arriba
    const statusSelectEl = document.getElementById('status');
    if (totalAmountInput && statusSelectEl) {
        const totalAmount = parseFloat(totalAmountInput.value) || 0;
        if (total >= totalAmount && totalAmount > 0) {
            statusSelectEl.value = 'pagado';
        } else if (total > 0) {
            statusSelectEl.value = 'pendiente';
        }
    }
}

// Event listener para añadir pago
document.getElementById('addExpensePaymentBtn')?.addEventListener('click', addExpensePayment);

// Actualizar total inicial
updateExpensePaymentsTotal();

// Añadir listeners a pagos existentes
document.querySelectorAll('#expensePaymentsContainer .expense-payment-amount').forEach(input => {
    input.addEventListener('input', updateExpensePaymentsTotal);
    input.addEventListener('focus', function() {
        if (parseFloat(this.value) === 0) {
            this.value = '';
        }
    });
});

}); // Cierre del DOMContentLoaded
</script>
@endpush
@endsection
