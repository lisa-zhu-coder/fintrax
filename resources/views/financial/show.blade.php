@extends('layouts.app')

@section('title', 'Detalle de Registro Financiero')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Detalle de Registro Financiero</h1>
                <p class="text-sm text-slate-500">Información completa del registro</p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->hasPermission('financial.registros.edit') && $entry && $entry->id)
                <a href="{{ route('financial.edit', $entry->id) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Editar
                </a>
                @endif
                <a href="{{ route('financial.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    ← Volver
                </a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <span class="text-xs font-semibold text-slate-500">Fecha</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $entry->date->format('d/m/Y') }}</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Tienda</span>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $entry->store->name }}</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Tipo</span>
                <div class="mt-1">
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium 
                        {{ $entry->type === 'income' ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ $entry->type === 'expense' ? 'bg-rose-100 text-rose-700' : '' }}
                        {{ $entry->type === 'daily_close' ? 'bg-blue-100 text-blue-700' : '' }}
                        {{ $entry->type === 'expense_refund' ? 'bg-amber-100 text-amber-700' : '' }}
                    ">
                        {{ ucfirst(str_replace('_', ' ', $entry->type)) }}
                    </span>
                </div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Importe</span>
                <div class="mt-1 text-lg font-semibold {{ $entry->type === 'income' ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ number_format($entry->amount, 2, ',', '.') }} €
                </div>
            </div>

            @if($entry->type === 'daily_close')
                <!-- Detalles de cierre diario -->
                <div class="md:col-span-2 space-y-4">
                    <div class="rounded-xl border-2 border-brand-100 bg-brand-50/30 p-4 ring-1 ring-brand-100">
                        <h3 class="mb-3 text-sm font-semibold text-brand-900">Conteo de caja</h3>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                            <div>
                                <span class="text-xs text-slate-500">Efectivo inicial</span>
                                <div class="text-sm font-semibold">{{ number_format($entry->cash_initial ?? 0, 2, ',', '.') }} €</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Tarjeta (TPV)</span>
                                <div class="text-sm font-semibold">{{ number_format($entry->tpv ?? 0, 2, ',', '.') }} €</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Efectivo contado</span>
                                <div class="text-sm font-semibold">{{ number_format($entry->calculateCashTotal(), 2, ',', '.') }} €</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Efectivo retirado</span>
                                @php
                                    $cashCounted = $entry->calculateCashTotal();
                                    $cashInitial = (float) ($entry->cash_initial ?? 0);
                                    $cashWithdrawn = round($cashCounted - $cashInitial, 2);
                                @endphp
                                <div class="text-sm font-semibold {{ $cashWithdrawn >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($cashWithdrawn, 2, ',', '.') }} €
                                </div>
                            </div>
                        </div>
                        @if($entry->cash_count && count($entry->cash_count) > 0)
                        <div class="mt-3 text-xs text-slate-500">Desglose:</div>
                        <div class="mt-1 grid grid-cols-3 gap-2 text-xs">
                            @foreach($entry->cash_count as $denomination => $count)
                                @if($count > 0)
                                <div>{{ number_format($denomination, 2, ',', '.') }} €: {{ $count }} unidades</div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                    </div>

                    @if($entry->expense_items && count($entry->expense_items) > 0)
                    <div class="rounded-xl border-2 border-rose-100 bg-rose-50/30 p-4 ring-1 ring-rose-100">
                        <h3 class="mb-3 text-sm font-semibold text-rose-900">Gastos detallados</h3>
                        <div class="space-y-2">
                            @foreach($entry->expense_items as $item)
                            <div class="flex justify-between text-sm">
                                <span>{{ $item['concept'] ?? '—' }}</span>
                                <span class="font-semibold">{{ number_format($item['amount'] ?? 0, 2, ',', '.') }} €</span>
                            </div>
                            @endforeach
                            <div class="mt-2 border-t border-rose-200 pt-2 flex justify-between font-semibold">
                                <span>Total gastos</span>
                                <span>{{ number_format($entry->expenses ?? 0, 2, ',', '.') }} €</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    @php $vouchersEnabled = $dailyCloseSettings['vouchers_enabled'] ?? true; @endphp
                    @if(($vouchersEnabled) && ($entry->vouchers_in || $entry->vouchers_out))
                    <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
                        <h3 class="mb-3 text-sm font-semibold text-emerald-900">Vales</h3>
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <span class="text-xs text-slate-500">Entrada</span>
                                <div class="font-semibold">{{ number_format($entry->vouchers_in ?? 0, 2, ',', '.') }} €</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Salida</span>
                                <div class="font-semibold">{{ number_format($entry->vouchers_out ?? 0, 2, ',', '.') }} €</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Resultado</span>
                                <div class="font-semibold">{{ number_format($entry->vouchers_result ?? 0, 2, ',', '.') }} €</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($entry->shopify_cash !== null || $entry->shopify_tpv !== null)
                    @php
                        $posLabel = $dailyCloseSettings['pos_label'] ?? 'Sistema POS';
                        $posCashLabel = $dailyCloseSettings['pos_cash_label'] ?? 'Sistema POS · Efectivo (€)';
                        $posCardLabel = $dailyCloseSettings['pos_card_label'] ?? 'Sistema POS · Tarjeta (€)';
                    @endphp
                    <div class="rounded-xl border-2 border-blue-100 bg-blue-50/30 p-4 ring-1 ring-blue-100">
                        <h3 class="mb-3 text-sm font-semibold text-blue-900">{{ $posLabel }}</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            @if($entry->shopify_cash !== null)
                            <div>
                                <span class="text-xs text-slate-500">{{ $posCashLabel }}</span>
                                <div class="font-semibold">{{ number_format($entry->shopify_cash, 2, ',', '.') }} €</div>
                                @php
                                    $cashDiscrepancy = $entry->calculateCashDiscrepancy();
                                @endphp
                                @if($cashDiscrepancy !== null)
                                <div class="mt-1 text-xs {{ $cashDiscrepancy === 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                                    Discrepancia: {{ number_format($cashDiscrepancy, 2, ',', '.') }} €
                                </div>
                                @endif
                            </div>
                            @endif
                            @if($entry->shopify_tpv !== null)
                            <div>
                                <span class="text-xs text-slate-500">{{ $posCardLabel }}</span>
                                <div class="font-semibold">{{ number_format($entry->shopify_tpv, 2, ',', '.') }} €</div>
                                @php
                                    $tpvDiscrepancy = $entry->calculateTpvDiscrepancy();
                                @endphp
                                @if($tpvDiscrepancy !== null)
                                <div class="mt-1 text-xs {{ $tpvDiscrepancy === 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                                    Discrepancia: {{ number_format($tpvDiscrepancy, 2, ',', '.') }} €
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="rounded-xl border-2 border-slate-200 bg-slate-50 p-4 ring-1 ring-slate-200">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Resumen</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Ventas en efectivo:</span>
                                <span class="font-semibold">{{ number_format($entry->calculateComputedCashSales(), 2, ',', '.') }} €</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Ventas en tarjeta:</span>
                                <span class="font-semibold">{{ number_format($entry->tpv ?? 0, 2, ',', '.') }} €</span>
                            </div>
                            @if($vouchersEnabled)
                            <div class="flex justify-between">
                                <span>Vales:</span>
                                <span class="font-semibold">{{ number_format($entry->vouchers_result ?? 0, 2, ',', '.') }} €</span>
                            </div>
                            @endif
                            <div class="mt-2 border-t border-slate-200 pt-2 flex justify-between font-semibold">
                                <span>Total ventas:</span>
                                <span>{{ number_format($entry->calculateTotalSales(), 2, ',', '.') }} €</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($entry->concept)
            <div class="md:col-span-2">
                <span class="text-xs font-semibold text-slate-500">Concepto</span>
                <div class="mt-1 text-sm text-slate-900">{{ $entry->concept }}</div>
            </div>
            @endif

            @if($entry->notes)
            <div class="md:col-span-2">
                <span class="text-xs font-semibold text-slate-500">Notas</span>
                <div class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ $entry->notes }}</div>
            </div>
            @endif

            <div>
                <span class="text-xs font-semibold text-slate-500">Creado por</span>
                <div class="mt-1 text-sm text-slate-900">{{ $entry->creator->name ?? 'Sistema' }}</div>
            </div>

            <div>
                <span class="text-xs font-semibold text-slate-500">Fecha de creación</span>
                <div class="mt-1 text-sm text-slate-900">{{ $entry->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
