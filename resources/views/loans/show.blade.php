@extends('layouts.app')

@section('title', 'Préstamo: ' . $loan->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('loans.index') }}" class="text-brand-600 hover:underline">Préstamos</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">{{ $loan->name }}</span>
                </nav>
                <h1 class="text-lg font-semibold">{{ $loan->name }}</h1>
                <p class="text-sm text-slate-500">{{ $loan->loanType->name ?? '—' }} · {{ $loan->direction === 'company_owes' ? 'La empresa debe' : 'La empresa presta (nos deben)' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('loans.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
                @if(auth()->user()->hasPermission('loans.main.edit'))
                <a href="{{ route('loans.edit', $loan) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Editar préstamo</a>
                @endif
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @php
        $totalPaid = $loan->getTotalPaid();
        $accruedInterest = $loan->getAccruedInterest();
        $openingFee = (float) ($loan->opening_fee ?? 0);
        $balance = $loan->getBalance();
        $outstandingPrincipal = $loan->getOutstandingPrincipal();
        $remainingInstallments = $loan->getRemainingInstallmentsCount();
        $effectiveRate = $loan->getEffectiveAnnualRate();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Capital inicial</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ number_format($loan->principal_amount, 2, ',', '.') }} €</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Interés aplicado</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">
                @if($effectiveRate !== null)
                    {{ number_format($effectiveRate, 2, ',', '.') }}% {{ $loan->interest_type === 'variable' ? '(variable)' : '(fijo)' }}
                @else
                    —
                @endif
            </div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Comisión apertura</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ number_format($openingFee, 2, ',', '.') }} €</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Total pagado</div>
            <div class="mt-1 text-xl font-semibold text-emerald-700">{{ number_format($totalPaid, 2, ',', '.') }} €</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Saldo pendiente</div>
            <div class="mt-1 text-xl font-semibold {{ $balance < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($balance, 2, ',', '.') }} €</div>
        </div>
    </div>

    @if($loan->installments->isNotEmpty())
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Cuotas restantes</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ $remainingInstallments }}</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
            <div class="text-xs font-semibold text-slate-500">Capital pendiente (según plan)</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ number_format($outstandingPrincipal, 2, ',', '.') }} €</div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="text-base font-semibold mb-4">Tabla de amortización</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 rounded-tl">Nº</th>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2 text-right">Cuota</th>
                        <th class="px-3 py-2 text-right">Interés</th>
                        <th class="px-3 py-2 text-right">Amortización</th>
                        <th class="px-3 py-2 text-right rounded-tr">Capital restante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($loan->installments as $inst)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ $inst->installment_number }}</td>
                            <td class="px-3 py-2">{{ $inst->due_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($inst->payment_amount, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($inst->interest_amount, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right">{{ number_format($inst->principal_amount, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-medium">{{ number_format($inst->remaining_balance, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold">Pagos realizados</h2>
            @if(auth()->user()->hasPermission('loans.payments.create'))
            <button type="button" onclick="document.getElementById('paymentForm').classList.toggle('hidden')" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                Registrar pago
            </button>
            @endif
        </div>

        @if(auth()->user()->hasPermission('loans.payments.create'))
        <div id="paymentForm" class="hidden mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <form method="POST" action="{{ route('loans.payments.store', $loan) }}" class="flex flex-wrap items-end gap-4">
                @csrf
                <label class="min-w-[140px]">
                    <span class="block text-xs font-semibold text-slate-700 mb-1">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="min-w-[120px]">
                    <span class="block text-xs font-semibold text-slate-700 mb-1">Importe (€) *</span>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="min-w-[200px] flex-1">
                    <span class="block text-xs font-semibold text-slate-700 mb-1">Comentario</span>
                    <input type="text" name="comment" value="{{ old('comment') }}" placeholder="Opcional" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar pago</button>
            </form>
            @if($errors->any())
                <div class="mt-2 text-sm text-rose-600">{{ $errors->first() }}</div>
            @endif
        </div>
        @endif

        @if($loan->payments->isEmpty())
            <p class="text-slate-500 py-4">No hay pagos registrados.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2">Fecha</th>
                            <th class="px-3 py-2 text-right">Importe</th>
                            <th class="px-3 py-2">Origen</th>
                            <th class="px-3 py-2">Comentario</th>
                            <th class="px-3 py-2 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($loan->payments as $payment)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">{{ $payment->date->format('d/m/Y') }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ number_format($payment->amount, 2, ',', '.') }} €</td>
                                <td class="px-3 py-2">{{ $payment->source === 'bank_reconciliation' ? 'Conciliación bancaria' : 'Manual' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $payment->comment ?? '—' }}</td>
                                <td class="px-3 py-2 text-right">
                                    @if($payment->source === 'manual' && auth()->user()->hasPermission('loans.payments.edit'))
                                    <a href="{{ route('loans.payments.edit', [$loan, $payment]) }}" class="text-brand-600 hover:underline">Editar</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('loans.payments.delete'))
                                    <form method="POST" action="{{ route('loans.payments.destroy', [$loan, $payment]) }}" class="inline" onsubmit="return confirm('¿Eliminar este pago?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline ml-2">Eliminar</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
