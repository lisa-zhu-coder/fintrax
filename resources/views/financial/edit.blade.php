@extends('layouts.app')

@section('title', 'Editar Registro Financiero')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Editar Registro Financiero</h1>
                <p class="text-sm text-slate-500">Modifica los datos del registro</p>
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
        <form method="POST" action="{{ route('financial.update', $entry->id) }}" class="space-y-6" id="financialForm">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', $entry->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('date') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                    <select name="store_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id', $entry->store_id) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo *</span>
                    <select name="type" id="entryType" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="daily_close" {{ old('type', $entry->type) === 'daily_close' ? 'selected' : '' }}>Cierre diario</option>
                        <option value="expense" {{ old('type', $entry->type) === 'expense' ? 'selected' : '' }}>Gasto</option>
                        <option value="income" {{ old('type', $entry->type) === 'income' ? 'selected' : '' }}>Ingreso</option>
                        <option value="expense_refund" {{ old('type', $entry->type) === 'expense_refund' ? 'selected' : '' }}>Devolución de gasto</option>
                    </select>
                </label>

                <label class="block" id="conceptLabel" style="display: {{ ($entry->type === 'daily_close' || $entry->type === 'expense') ? 'none' : 'block' }};">
                    <span class="text-xs font-semibold text-slate-700">Concepto</span>
                    <input type="text" name="concept" id="concept" value="{{ old('concept', $entry->concept) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>

                <label class="block" id="amountLabel" style="display: {{ ($entry->type === 'daily_close' || $entry->type === 'expense') ? 'none' : 'block' }};">
                    <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" value="{{ old('amount', $entry->amount) }}" {{ ($entry->type !== 'daily_close' && $entry->type !== 'expense') ? 'required' : '' }} class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>

            <!-- Sección de Cierre Diario -->
            <div id="sectionDailyClose" class="{{ $entry->type === 'daily_close' ? '' : 'hidden' }} space-y-4">
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
                            <input type="number" name="cash_initial" id="cashInitial" step="0.01" min="0" value="{{ old('cash_initial', $entry->cash_initial ?? 0) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Tarjeta (€) *</span>
                            <input type="number" name="tpv" id="tpv" step="0.01" min="0" value="{{ old('tpv', $entry->tpv ?? 0) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Gastos en efectivo (€)</span>
                            <input type="number" name="cash_expenses" id="cashExpenses" step="0.01" value="{{ old('cash_expenses', $entry->cash_expenses ?? 0) }}" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                            <div class="mt-1 text-xs text-slate-500">Se calcula automáticamente sumando los gastos del cierre (se permiten valores negativos).</div>
                        </label>
                    </div>

                    <!-- Conteo de monedas y billetes (renderizado en servidor para que siempre esté disponible al editar) -->
                    <div class="mt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700">Conteo de efectivo</span>
                            <div class="text-xs text-slate-600">
                                Total contado: <span id="cashCountTotal" class="font-semibold text-brand-700">{{ $entry->type === 'daily_close' ? number_format($entry->calculateCashTotal(), 2, ',', '.') . ' €' : '0,00 €' }}</span>
                            </div>
                        </div>
                        <div id="cashCountContainer" class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-5">
                            @php
                                $denominations = [
                                    ['value' => 500, 'label' => '500 €'],
                                    ['value' => 200, 'label' => '200 €'],
                                    ['value' => 100, 'label' => '100 €'],
                                    ['value' => 50, 'label' => '50 €'],
                                    ['value' => 20, 'label' => '20 €'],
                                    ['value' => 10, 'label' => '10 €'],
                                    ['value' => 5, 'label' => '5 €'],
                                    ['value' => 2, 'label' => '2 €'],
                                    ['value' => 1, 'label' => '1 €'],
                                    ['value' => 0.5, 'label' => '0,50 €'],
                                    ['value' => 0.2, 'label' => '0,20 €'],
                                    ['value' => 0.1, 'label' => '0,10 €'],
                                    ['value' => 0.05, 'label' => '0,05 €'],
                                    ['value' => 0.02, 'label' => '0,02 €'],
                                    ['value' => 0.01, 'label' => '0,01 €'],
                                ];
                                $cashCount = old('cash_count', $entry->cash_count ?? []);
                            @endphp
                            @foreach($denominations as $denom)
                                @php
                                    $val = $cashCount[ (string) $denom['value'] ] ?? $cashCount[ $denom['value'] ] ?? 0;
                                @endphp
                                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 ring-1 ring-slate-100">
                                    <label class="flex-1">
                                        <span class="block text-xs font-medium text-slate-700">{{ $denom['label'] }}</span>
                                        <input type="number" min="0" step="1" data-denom="{{ $denom['value'] }}"
                                            name="cash_count[{{ $denom['value'] }}]"
                                            value="{{ $val }}"
                                            placeholder="0"
                                            autocomplete="off"
                                            class="cash-count-input mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-400"/>
                                    </label>
                                </div>
                            @endforeach
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
                            $existingExpenseItems = old('expense_items', $entry->expense_items ?? []);
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
                            <input type="number" name="vouchers_in" id="vouchersIn" step="0.01" min="0" value="{{ old('vouchers_in', $entry->vouchers_in ?? 0) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Vales salida (€)</span>
                            <input type="number" name="vouchers_out" id="vouchersOut" step="0.01" min="0" value="{{ old('vouchers_out', $entry->vouchers_out ?? 0) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Resultado vales (€)</span>
                            <input type="number" name="vouchers_result" id="vouchersResult" step="0.01" value="{{ old('vouchers_result', $entry->vouchers_result ?? 0) }}" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none"/>
                        </label>
                    </div>
                </div>
                @else
                <input type="hidden" name="vouchers_in" value="{{ old('vouchers_in', $entry->vouchers_in ?? 0) }}">
                <input type="hidden" name="vouchers_out" value="{{ old('vouchers_out', $entry->vouchers_out ?? 0) }}">
                <input type="hidden" name="vouchers_result" value="{{ old('vouchers_result', $entry->vouchers_result ?? 0) }}">
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
                            <input type="number" name="shopify_cash" id="shopifyCash" step="0.01" min="0" value="{{ old('shopify_cash', $entry->shopify_cash) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">{{ $posCardLabel }}</span>
                            <input type="number" name="shopify_tpv" id="shopifyTpv" step="0.01" min="0" value="{{ old('shopify_tpv', $entry->shopify_tpv) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                    </div>
                </div>

                <!-- Resultado (valores iniciales desde servidor para cierre diario) -->
                @php
                    $isDailyClose = $entry->type === 'daily_close';
                    $initTotalSales = $isDailyClose ? ($entry->amount ?? 0) : 0;
                    $initComputedCash = $isDailyClose ? ($entry->calculateComputedCashSales() ?? 0) : 0;
                    $initTpv = $isDailyClose ? ($entry->tpv ?? 0) : 0;
                    $initVouchers = $isDailyClose ? ($entry->vouchers_result ?? 0) : 0;
                    $initExpenses = $isDailyClose ? ($entry->expenses ?? 0) : 0;
                    $initWithdraw = $isDailyClose ? (($entry->calculateCashTotal() ?? 0) - ($entry->cash_initial ?? 0)) : 0;
                @endphp
                <div class="rounded-xl border-2 border-slate-200 bg-slate-50 p-4 ring-1 ring-slate-200">
                    <div class="mb-3 flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-700">
                            <path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="text-sm font-semibold text-slate-900">Resultado</span>
                    </div>
                    <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                        <div class="text-xs font-semibold text-slate-700">Ventas totales (€)</div>
                        <div id="totalSales" class="mt-1 text-lg font-semibold text-slate-900">{{ $isDailyClose ? number_format($initTotalSales, 2, ',', '.') . ' €' : '0,00 €' }}</div>
                        <div class="mt-2 space-y-1 text-xs text-slate-600">
                            <div>Efectivo: <span id="computedCashSales" class="font-semibold text-slate-900">{{ $isDailyClose ? number_format($initComputedCash, 2, ',', '.') . ' €' : '0,00 €' }}</span></div>
                            <div>Datáfono: <span id="computedTpvSales" class="font-semibold text-slate-900">{{ $isDailyClose ? number_format($initTpv, 2, ',', '.') . ' €' : '0,00 €' }}</span></div>
                            @if($vouchersEnabled)
                            <div>Vales: <span id="computedVouchersSales" class="font-semibold text-slate-900">{{ $isDailyClose ? number_format($initVouchers, 2, ',', '.') . ' €' : '0,00 €' }}</span></div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 rounded-xl bg-white p-3 ring-1 ring-slate-100">
                        <div class="text-xs font-semibold text-slate-700">Gastos totales (€)</div>
                        <div id="totalExpensesDisplay" class="mt-1 text-lg font-semibold text-slate-900">{{ $isDailyClose ? number_format($initExpenses, 2, ',', '.') . ' €' : '0,00 €' }}</div>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                            <div class="text-xs font-semibold text-slate-700">Discrepancia (efectivo vs Shopify)</div>
                            <div id="cashDiscrepancy" class="mt-1 text-sm font-semibold">@if($isDailyClose && $entry->shopify_cash !== null){{ number_format(($initComputedCash ?? 0) - (float)$entry->shopify_cash, 2, ',', '.') }} €@else—@endif</div>
                        </div>
                        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                            <div class="text-xs font-semibold text-slate-700">Discrepancia (tarjeta vs Shopify)</div>
                            <div id="tpvDiscrepancy" class="mt-1 text-sm font-semibold">@if($isDailyClose && $entry->shopify_tpv !== null){{ number_format(($initTpv ?? 0) - (float)$entry->shopify_tpv, 2, ',', '.') }} €@else—@endif</div>
                        </div>
                        <div class="rounded-xl bg-white p-3 ring-1 ring-slate-100">
                            <div class="text-xs font-semibold text-slate-700">Retirar efectivo</div>
                            <div id="withdrawAmount" class="mt-1 text-sm font-semibold text-slate-900">{{ $isDailyClose ? number_format($initWithdraw, 2, ',', '.') . ' €' : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Gasto simple -->
            <div id="sectionExpense" class="{{ $entry->type === 'expense' ? '' : 'hidden' }} space-y-4">
                <div class="rounded-xl border-2 border-rose-100 bg-rose-50/30 p-4 ring-1 ring-rose-100">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Categoría del gasto</span>
                            <select name="expense_category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="alquiler" {{ old('expense_category', $entry->expense_category) === 'alquiler' ? 'selected' : '' }}>Alquiler</option>
                                <option value="impuestos" {{ old('expense_category', $entry->expense_category) === 'impuestos' ? 'selected' : '' }}>Impuestos</option>
                                <option value="seguridad_social" {{ old('expense_category', $entry->expense_category) === 'seguridad_social' ? 'selected' : '' }}>Seguridad Social</option>
                                <option value="suministros" {{ old('expense_category', $entry->expense_category) === 'suministros' ? 'selected' : '' }}>Suministros</option>
                                <option value="servicios_profesionales" {{ old('expense_category', $entry->expense_category) === 'servicios_profesionales' ? 'selected' : '' }}>Servicios profesionales</option>
                                <option value="sueldos" {{ old('expense_category', $entry->expense_category) === 'sueldos' ? 'selected' : '' }}>Sueldos</option>
                                <option value="miramira" {{ old('expense_category', $entry->expense_category) === 'miramira' ? 'selected' : '' }}>Miramira</option>
                                <option value="mercaderia" {{ old('expense_category', $entry->expense_category) === 'mercaderia' ? 'selected' : '' }}>Mercadería</option>
                                <option value="equipamiento" {{ old('expense_category', $entry->expense_category) === 'equipamiento' ? 'selected' : '' }}>Equipamiento</option>
                                <option value="otros" {{ old('expense_category', $entry->expense_category) === 'otros' ? 'selected' : '' }}>Otros</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Método de pago</span>
                            <select name="expense_payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="cash" {{ old('expense_payment_method', $entry->expense_payment_method) === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                <option value="bank" {{ old('expense_payment_method', $entry->expense_payment_method) === 'bank' ? 'selected' : '' }}>Banco</option>
                                <option value="card" {{ old('expense_payment_method', $entry->expense_payment_method) === 'card' ? 'selected' : '' }}>Tarjeta</option>
                            </select>
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-xs font-semibold text-slate-700">Concepto del gasto</span>
                            <input type="text" name="expense_concept" value="{{ old('expense_concept', $entry->expense_concept) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Importe total (€) *</span>
                            <input type="number" name="total_amount" id="totalAmount" step="0.01" min="0" value="{{ old('total_amount', $entry->total_amount ?? $entry->expense_amount ?? $entry->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Estado</span>
                            <select name="status" id="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="pendiente" {{ old('status', $entry->status ?? 'pendiente') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="pagado" {{ old('status', $entry->status ?? 'pendiente') === 'pagado' ? 'selected' : '' }}>Pagado</option>
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
                            $existingPayments = old('expense_payments');
                            if ($existingPayments === null) {
                                try {
                                    $existingPayments = $entry->expensePayments ?? [];
                                } catch (\Exception $e) {
                                    $existingPayments = [];
                                }
                            }
                        @endphp
                        @foreach($existingPayments as $i => $payment)
                            @php 
                                $idx = is_numeric($i) ? (int) $i : $loop->index;
                                $paymentDate = is_object($payment) ? $payment->date->format('Y-m-d') : ($payment['date'] ?? '');
                                $paymentMethod = is_object($payment) ? $payment->method : ($payment['method'] ?? '');
                                $paymentAmount = is_object($payment) ? $payment->amount : ($payment['amount'] ?? '');
                            @endphp
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2" data-payment-index="{{ $idx }}">
                                <input type="date" name="expense_payments[{{ $idx }}][date]" value="{{ $paymentDate }}" required class="w-40 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
                                <select name="expense_payments[{{ $idx }}][method]" required class="w-32 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2">
                                    <option value="">Método</option>
                                    <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                    <option value="bank" {{ $paymentMethod === 'bank' ? 'selected' : '' }}>Banco</option>
                                </select>
                                <input type="number" name="expense_payments[{{ $idx }}][amount]" step="0.01" min="0" value="{{ $paymentAmount }}" placeholder="0.00" required class="expense-payment-amount flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>
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
                        <input type="checkbox" id="expenseSplitStores" {{ $entry->store_split ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500"/>
                        <span class="text-sm font-semibold text-slate-700">Dividir este gasto entre tiendas</span>
                    </label>
                    <div id="expenseSplitContainer" class="{{ $entry->store_split ? '' : 'hidden' }} space-y-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs font-semibold text-slate-700 mb-2">Selecciona las tiendas que participan:</div>
                            <div id="expenseSplitStoreCheckboxes" class="space-y-2">
                                @php
                                    $splitStores = $entry->store_split['stores'] ?? [];
                                @endphp
                                @foreach($stores as $store)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="expense_split_stores[]" value="{{ $store->id }}" {{ in_array($store->id, $splitStores) ? 'checked' : '' }} class="expense-split-store-checkbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500"/>
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
            <div id="sectionIncome" class="{{ $entry->type === 'income' ? '' : 'hidden' }} space-y-4">
                <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Categoría del ingreso</span>
                            <select name="income_category" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="servicios_financieros" {{ old('income_category', $entry->income_category) === 'servicios_financieros' ? 'selected' : '' }}>Servicios financieros</option>
                                <option value="ventas" {{ old('income_category', $entry->income_category) === 'ventas' ? 'selected' : '' }}>Ventas</option>
                                <option value="otros" {{ old('income_category', $entry->income_category) === 'otros' ? 'selected' : '' }}>Otros</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Concepto del ingreso</span>
                            <input type="text" name="income_concept" value="{{ old('income_concept', $entry->income_concept) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Método de pago</span>
                            <select name="expense_payment_method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                                <option value="">Selecciona…</option>
                                <option value="cash" {{ old('expense_payment_method', $entry->expense_payment_method) === 'cash' ? 'selected' : '' }}>Efectivo</option>
                                <option value="bank" {{ old('expense_payment_method', $entry->expense_payment_method) === 'bank' ? 'selected' : '' }}>Banco</option>
                                <option value="card" {{ old('expense_payment_method', $entry->expense_payment_method) === 'card' ? 'selected' : '' }}>Tarjeta</option>
                                <option value="datafono" {{ old('expense_payment_method', $entry->expense_payment_method) === 'datafono' ? 'selected' : '' }}>Datáfono</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sección: Devolución -->
            <div id="sectionRefund" class="{{ $entry->type === 'expense_refund' ? '' : 'hidden' }} space-y-4">
                <div class="rounded-xl border-2 border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-100">
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-700">Concepto de la devolución</span>
                        <input type="text" name="refund_concept" value="{{ old('refund_concept', $entry->refund_concept) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    </label>
                </div>
            </div>

            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Notas</span>
                <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">{{ old('notes', $entry->notes) }}</textarea>
            </label>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('financial.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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
<script type="text/javascript">
// — Botón "Añadir gasto" (independiente de runEditFormInit para que siempre funcione) —
(function() {
    function getNextExpenseIndex() {
        var container = document.getElementById('expenseItemsContainer');
        if (!container) return 0;
        var max = -1;
        container.querySelectorAll('[data-expense-index]').forEach(function(el) {
            var idx = parseInt(el.getAttribute('data-expense-index'), 10) || 0;
            if (idx > max) max = idx;
        });
        return max + 1;
    }
    function addExpenseRow() {
        var container = document.getElementById('expenseItemsContainer');
        if (!container) return;
        var index = getNextExpenseIndex();
        var div = document.createElement('div');
        div.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2';
        div.setAttribute('data-expense-index', index);
        div.innerHTML = '<input type="text" name="expense_items[' + index + '][concept]" placeholder="Concepto" required class="flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>' +
            '<input type="number" name="expense_items[' + index + '][amount]" step="0.01" placeholder="0.00" required class="w-28 rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none ring-brand-200 focus:ring-2"/>' +
            '<button type="button" class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50" aria-label="Eliminar gasto"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
        var removeBtn = div.querySelector('button');
        removeBtn.addEventListener('click', function() {
            div.remove();
            if (window._editFormUpdateDailyCloseTotals) window._editFormUpdateDailyCloseTotals();
        });
        container.appendChild(div);
        div.querySelectorAll('input').forEach(function(input) {
            input.addEventListener('input', function() {
                if (window._editFormUpdateDailyCloseTotals) window._editFormUpdateDailyCloseTotals();
            });
        });
        if (window._editFormUpdateDailyCloseTotals) window._editFormUpdateDailyCloseTotals();
    }
    function onDocClick(e) {
        var btn = e.target && e.target.closest && e.target.closest('#addExpenseItemBtn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        addExpenseRow();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { document.addEventListener('click', onDocClick); });
    } else {
        document.addEventListener('click', onDocClick);
    }
})();

// Ejecutar cuando el DOM esté listo (si el script se carga al final del body, DOMContentLoaded ya pudo haber disparado)
function runEditFormInit() {
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

// Datos existentes del modelo para el modo edición
const existingCashCount = @json($entry->cash_count ?? []);

let expenseItemIndex = document.querySelectorAll('#expenseItemsContainer [data-expense-index]').length || 0;

// Asegurarse de que expenseItemIndex sea mayor que el último índice existente
document.querySelectorAll('#expenseItemsContainer [data-expense-index]').forEach(el => {
    const idx = parseInt(el.dataset.expenseIndex) || 0;
    if (idx >= expenseItemIndex) {
        expenseItemIndex = idx + 1;
    }
});

// Añadir listeners a los inputs del conteo (ya existan por servidor o por renderCashCount)
function attachCashCountListeners() {
    const container = document.getElementById('cashCountContainer');
    if (!container) return;
    container.querySelectorAll('.cash-count-input').forEach(input => {
        input.removeAttribute('readonly');
        input.removeAttribute('disabled');
        input.disabled = false;
        // Evitar duplicar listeners
        if (input._cashCountListenerAttached) return;
        input._cashCountListenerAttached = true;
        input.addEventListener('input', updateDailyCloseTotals);
        input.addEventListener('change', updateDailyCloseTotals);
        input.addEventListener('change', updateCashCountTotal);
        input.addEventListener('focus', function() {
            if (parseFloat(this.value) === 0) this.value = '';
        });
        input.addEventListener('keydown', function(e) {
            if (parseFloat(this.value) === 0 && /[0-9]/.test(e.key)) this.value = '';
        });
    });
    updateCashCountTotal();
}

// Renderizar denominaciones por JS (solo si el contenedor está vacío, p. ej. al cambiar tipo)
function renderCashCount() {
    const container = document.getElementById('cashCountContainer');
    if (!container) return;
    container.innerHTML = '';
    DENOMINATIONS.forEach((denom) => {
        let count = 0;
        if (existingCashCount && typeof existingCashCount === 'object') {
            count = existingCashCount[String(denom.value)] || existingCashCount[denom.value] || 0;
        }
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 ring-1 ring-slate-100';
        div.innerHTML = `
            <label class="flex-1">
                <span class="block text-xs font-medium text-slate-700">${denom.label}</span>
                <input type="number" min="0" step="1" data-denom="${denom.value}"
                    name="cash_count[${denom.value}]" value="${count}" placeholder="0" autocomplete="off"
                    class="cash-count-input mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-400"/>
            </label>
        `;
        container.appendChild(div);
    });
    attachCashCountListeners();
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
    
    // Calcular total de gastos - usar múltiples selectores para asegurar que capture todos los campos
    let totalExpenses = 0;
    
    // Selector 1: campos con name que empieza con expense_items y termina con [amount]
    document.querySelectorAll('[name^="expense_items"][name$="[amount]"]').forEach(input => {
        const value = parseFloat(input.value) || 0;
        totalExpenses += value;
    });
    
    // Selector 2: campos dentro del contenedor de gastos (por si acaso el selector anterior no funciona)
    document.querySelectorAll('#expenseItemsContainer input[type="number"]').forEach(input => {
        if (input.name && input.name.includes('expense_items') && input.name.includes('amount')) {
            // Solo sumar si no se sumó ya con el selector anterior
            if (!input.matches('[name^="expense_items"][name$="[amount]"]')) {
                const value = parseFloat(input.value) || 0;
                totalExpenses += value;
            }
        }
    });
    
    const totalExpensesEl = document.getElementById('totalExpenses');
    if (totalExpensesEl) totalExpensesEl.textContent = formatEuro(totalExpenses);
    
    const totalExpensesDisplayEl = document.getElementById('totalExpensesDisplay');
    if (totalExpensesDisplayEl) totalExpensesDisplayEl.textContent = formatEuro(totalExpenses);
    
    const cashExpensesEl = document.getElementById('cashExpenses');
    if (cashExpensesEl) cashExpensesEl.value = totalExpenses.toFixed(2);
    
    // Calcular efectivo contado
    updateCashCountTotal();
    const cashCounted = calculateCashTotal();
    const cashInitialEl = document.getElementById('cashInitial');
    const cashInitial = cashInitialEl ? (parseFloat(cashInitialEl.value) || 0) : 0;
    const cashExpenses = totalExpenses;
    const computedCashSales = cashCounted - cashInitial + cashExpenses;

    // Retirar efectivo = efectivo total contado - efectivo inicial
    const withdraw = cashCounted - cashInitial;
    const withdrawEl = document.getElementById('withdrawAmount');
    if (withdrawEl) {
        withdrawEl.textContent = formatEuro(withdraw);
        withdrawEl.className = withdraw >= 0 ? 'mt-1 text-sm font-semibold text-emerald-700' : 'mt-1 text-sm font-semibold text-rose-700';
    }
    
    // Calcular vales
    const vouchersInEl = document.getElementById('vouchersIn');
    const vouchersOutEl = document.getElementById('vouchersOut');
    const vouchersIn = vouchersInEl ? (parseFloat(vouchersInEl.value) || 0) : 0;
    const vouchersOut = vouchersOutEl ? (parseFloat(vouchersOutEl.value) || 0) : 0;
    const vouchersResult = vouchersIn - vouchersOut;
    const vouchersResultEl = document.getElementById('vouchersResult');
    if (vouchersResultEl) vouchersResultEl.value = vouchersResult.toFixed(2);
    
    // Calcular ventas totales
    const tpvEl = document.getElementById('tpv');
    const tpv = tpvEl ? (parseFloat(tpvEl.value) || 0) : 0;
    const totalSales = tpv + computedCashSales + vouchersResult;
    
    const computedCashSalesEl = document.getElementById('computedCashSales');
    if (computedCashSalesEl) computedCashSalesEl.textContent = formatEuro(computedCashSales);
    
    const computedTpvSalesEl = document.getElementById('computedTpvSales');
    if (computedTpvSalesEl) computedTpvSalesEl.textContent = formatEuro(tpv);
    
    const computedVouchersSalesEl = document.getElementById('computedVouchersSales');
    if (computedVouchersSalesEl) computedVouchersSalesEl.textContent = formatEuro(vouchersResult);
    
    const totalSalesEl = document.getElementById('totalSales');
    if (totalSalesEl) totalSalesEl.textContent = formatEuro(totalSales);
    
    // Actualizar campos ocultos
    const amountEl = document.getElementById('amount');
    if (amountEl) amountEl.value = totalSales.toFixed(2);
    
    // Discrepancias
    const shopifyCashEl = document.getElementById('shopifyCash');
    const shopifyTpvEl = document.getElementById('shopifyTpv');
    const shopifyCash = shopifyCashEl ? parseFloat(shopifyCashEl.value) : null;
    const shopifyTpv = shopifyTpvEl ? parseFloat(shopifyTpvEl.value) : null;
    
    if (shopifyCash !== null && !isNaN(shopifyCash) && shopifyCash !== '') {
        const discrepancy = computedCashSales - shopifyCash;
        const discEl = document.getElementById('cashDiscrepancy');
        if (discEl) {
            discEl.textContent = formatEuro(discrepancy);
            discEl.className = discrepancy === 0 ? 'mt-1 text-sm font-semibold text-emerald-700' : 'mt-1 text-sm font-semibold text-amber-700';
        }
    } else {
        const discEl = document.getElementById('cashDiscrepancy');
        if (discEl) discEl.textContent = '—';
    }
    
    if (shopifyTpv !== null && !isNaN(shopifyTpv) && shopifyTpv !== '') {
        const discrepancy = tpv - shopifyTpv;
        const discEl = document.getElementById('tpvDiscrepancy');
        if (discEl) {
            discEl.textContent = formatEuro(discrepancy);
            discEl.className = discrepancy === 0 ? 'mt-1 text-sm font-semibold text-emerald-700' : 'mt-1 text-sm font-semibold text-amber-700';
        }
    } else {
        const discEl = document.getElementById('tpvDiscrepancy');
        if (discEl) discEl.textContent = '—';
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
document.getElementById('entryType').addEventListener('change', function() {
    const type = this.value;
    document.getElementById('sectionDailyClose').classList.toggle('hidden', type !== 'daily_close');
    document.getElementById('sectionExpense').classList.toggle('hidden', type !== 'expense');
    document.getElementById('sectionIncome').classList.toggle('hidden', type !== 'income');
    document.getElementById('sectionRefund').classList.toggle('hidden', type !== 'expense_refund');
    toggleDailyCloseRequired(type === 'daily_close');
    
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
    
    if (type === 'daily_close') {
        const container = document.getElementById('cashCountContainer');
        if (container && container.querySelectorAll('.cash-count-input').length > 0) {
            attachCashCountListeners();
        } else {
            renderCashCount();
        }
        setTimeout(() => updateDailyCloseTotals(), 100);
    }
});

// El botón "Añadir gasto" se maneja solo en el script independiente al inicio (una sola fila por clic)
document.getElementById('cashInitial')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('cashInitial')?.addEventListener('change', updateDailyCloseTotals);
document.getElementById('tpv')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('tpv')?.addEventListener('change', updateDailyCloseTotals);
document.getElementById('vouchersIn')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('vouchersOut')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('shopifyCash')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('shopifyCash')?.addEventListener('change', updateDailyCloseTotals);
document.getElementById('shopifyTpv')?.addEventListener('input', updateDailyCloseTotals);
document.getElementById('shopifyTpv')?.addEventListener('change', updateDailyCloseTotals);

// Inicializar según tipo
const initialType = document.getElementById('entryType').value;
toggleDailyCloseRequired(initialType === 'daily_close');

// Las secciones ya están configuradas en el HTML según el tipo inicial
// Solo necesitamos asegurarnos de que los campos de concepto e importe estén correctos
const conceptLabel = document.getElementById('conceptLabel');
const amountLabel = document.getElementById('amountLabel');
const conceptInput = document.getElementById('concept');
const amountInput = document.getElementById('amount');

if (initialType === 'daily_close' || initialType === 'expense') {
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

// Inicialización para cierres diarios (los campos ya vienen del servidor; solo enlazar listeners)
if (initialType === 'daily_close') {
    const sectionDailyClose = document.getElementById('sectionDailyClose');
    if (sectionDailyClose) sectionDailyClose.classList.remove('hidden');
    const container = document.getElementById('cashCountContainer');
    // Si el contenedor ya tiene inputs (renderizados en Blade), solo enlazar listeners
    if (container && container.querySelectorAll('.cash-count-input').length > 0) {
        attachCashCountListeners();
    } else {
        renderCashCount();
    }
    // Delegación de eventos: cualquier cambio en un .cash-count-input actualiza totales al instante
    if (container) {
        container.addEventListener('input', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('cash-count-input')) {
                updateCashCountTotal();
                updateDailyCloseTotals();
            }
        });
        container.addEventListener('change', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('cash-count-input')) {
                updateCashCountTotal();
                updateDailyCloseTotals();
            }
        });
    }
    setTimeout(() => {
        updateDailyCloseTotals();
        updateCashCountTotal();
    }, 50);
}

// Inicializar listeners de gastos existentes
document.querySelectorAll('#expenseItemsContainer input[name*="[amount]"]').forEach(input => {
    input.addEventListener('input', updateDailyCloseTotals);
    input.addEventListener('change', updateDailyCloseTotals);
});

// También añadir listeners usando delegación de eventos para capturar todos los campos
document.getElementById('expenseItemsContainer')?.addEventListener('input', function(e) {
    if (e.target.name && e.target.name.includes('expense_items') && e.target.name.includes('[amount]')) {
        updateDailyCloseTotals();
    }
});

document.getElementById('expenseItemsContainer')?.addEventListener('change', function(e) {
    if (e.target.name && e.target.name.includes('expense_items') && e.target.name.includes('[amount]')) {
        updateDailyCloseTotals();
    }
});

// Forzar cálculo inicial (edit) después de un delay más largo para asegurar que todo esté cargado
setTimeout(() => {
    if (initialType === 'daily_close') {
        updateDailyCloseTotals();
    }
}, 300);

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
const existingSplit = @json($entry->store_split ?? null);

function updateExpenseSplit() {
    if (!expenseSplitStores || !expenseSplitContainer) return;
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
                        name="expense_split_amounts[${store.id}]" 
                        step="0.01" 
                        min="0" 
                        value="${amount}"
                        class="expense-split-amount w-full rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm outline-none ring-brand-200 focus:ring-2"
                        data-store-id="${store.id}"
                    />
                </div>
                <div class="w-20 text-right text-xs font-semibold text-slate-600">
                    <span class="expense-split-percentage">${totalAmount > 0 ? ((parseFloat(amount) / totalAmount) * 100).toFixed(1) : '0.0'}%</span>
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
    if (!expenseAmountInput) return;
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
    
    if (expenseSplitTotal) expenseSplitTotal.textContent = sum.toFixed(2).replace('.', ',') + ' €';
    
    // Actualizar porcentajes
    const totalAmount = expenseAmountInput ? (parseFloat(expenseAmountInput.value) || 0) : 0;
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
    
    // Inicializar si está marcado
    if (expenseSplitStores.checked) {
        updateExpenseSplit();
    }
}

if (expenseAmountInput) {
    expenseAmountInput.addEventListener('input', function() {
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
const financialForm = document.getElementById('financialForm');
if (financialForm) {
    financialForm.addEventListener('submit', function(e) {
        if (expenseSplitStores && expenseSplitStores.checked && expenseAmountInput) {
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
        
        // Si el elemento con foco no es el botón de submit, prevenir el envío
        // Esto evita que Enter envíe el formulario accidentalmente
        if (focusedElement && focusedElement !== submitButton && focusedElement.type !== 'submit') {
            e.preventDefault();
            return false;
        }
    });
}

// Sincronizar campos de gastos (expenseAmountInput ya declarado más arriba)
const totalAmountInput = document.getElementById('totalAmount');
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

// Asegurar que expensePaymentIndex sea mayor que el último índice existente
document.querySelectorAll('#expensePaymentsContainer [data-payment-index]').forEach(el => {
    const idx = parseInt(el.dataset.paymentIndex) || 0;
    if (idx >= expensePaymentIndex) {
        expensePaymentIndex = idx + 1;
    }
});

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
    const totalAmountInput = document.getElementById('totalAmount');
    const statusSelect = document.getElementById('status');
    if (totalAmountInput && statusSelect) {
        const totalAmount = parseFloat(totalAmountInput.value) || 0;
        if (total >= totalAmount && totalAmount > 0) {
            statusSelect.value = 'pagado';
        } else if (total > 0) {
            statusSelect.value = 'pendiente';
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
    // Exponer funciones para que la delegación de eventos pueda llamarlas
    window._editFormUpdateCashCountTotal = updateCashCountTotal;
    window._editFormUpdateDailyCloseTotals = updateDailyCloseTotals;
} // Cierre de runEditFormInit

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runEditFormInit);
} else {
    runEditFormInit();
}

// Delegación de eventos: actualizar totales al instante al cambiar cualquier billete/moneda (fallback por si el init no enlazó)
setTimeout(function() {
    var container = document.getElementById('cashCountContainer');
    if (!container) return;
    container.addEventListener('input', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('cash-count-input')) {
            if (window._editFormUpdateCashCountTotal) window._editFormUpdateCashCountTotal();
            if (window._editFormUpdateDailyCloseTotals) window._editFormUpdateDailyCloseTotals();
        }
    });
    container.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('cash-count-input')) {
            if (window._editFormUpdateCashCountTotal) window._editFormUpdateCashCountTotal();
            if (window._editFormUpdateDailyCloseTotals) window._editFormUpdateDailyCloseTotals();
        }
    });
}, 150);
</script>
@endpush
@endsection
