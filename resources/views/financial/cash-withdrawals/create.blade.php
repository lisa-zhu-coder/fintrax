@extends('layouts.app')

@section('title', 'Recoger Efectivo')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Recoger Efectivo</h1>
                <p class="text-sm text-slate-500">Registra un retiro de efectivo de una tienda</p>
            </div>
            <a href="{{ isset($redirectTo) && $redirectTo ? $redirectTo : route('financial.cash-control') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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

        <form method="POST" action="{{ route('financial.cash-withdrawals.store') }}" class="space-y-6">
            @csrf
            @if(!empty($redirectTo))
            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" id="cashWithdrawalDate" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('date') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                    <p class="mt-1 text-xs text-slate-500">Fecha en que se realizó la recogida</p>
                    @error('date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Mes correspondiente *</span>
                    <select name="reporting_month" id="reportingMonth" required class="mt-1 w-full rounded-xl border {{ $errors->has('reporting_month') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona el mes al que corresponde</option>
                        @forelse($availableMonths as $m)
                            @php $defaultMonth = old('reporting_month', isset($defaultReportingMonth) && $defaultReportingMonth ? $defaultReportingMonth : substr(old('date', now()->format('Y-m-d')), 0, 7)); @endphp
                            <option value="{{ $m }}" {{ $defaultMonth === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->locale('es')->isoFormat('MMMM YYYY') }}</option>
                        @empty
                            <option value="" disabled>No hay meses con registro en control de efectivo</option>
                        @endforelse
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Mes en el que se contabiliza esta recogida en el control de efectivo</p>
                    @error('reporting_month')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tienda *</span>
                    <select name="store_id" required class="mt-1 w-full rounded-xl border {{ $errors->has('store_id') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona una tienda</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id', $defaultStoreId ?? null) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                    @error('store_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Cartera *</span>
                    <select name="cash_wallet_id" required class="mt-1 w-full rounded-xl border {{ $errors->has('cash_wallet_id') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona una cartera</option>
                        @foreach($cashWallets as $wallet)
                            <option value="{{ $wallet->id }}" {{ old('cash_wallet_id') == $wallet->id ? 'selected' : '' }}>{{ $wallet->name }}</option>
                        @endforeach
                    </select>
                    @error('cash_wallet_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mt-1 w-full rounded-xl border {{ $errors->has('amount') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="0.00"/>
                    @error('amount')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ isset($redirectTo) && $redirectTo ? $redirectTo : route('financial.cash-control') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var dateInput = document.getElementById('cashWithdrawalDate');
    var monthSelect = document.getElementById('reportingMonth');
    if (!dateInput || !monthSelect) return;
    var options = Array.from(monthSelect.querySelectorAll('option[value]')).map(function(o) { return o.value; });
    function setDefaultMonth() {
        var val = dateInput.value;
        if (!val) return;
        var y = val.slice(0, 4), m = val.slice(5, 7);
        var monthVal = y + '-' + m;
        if (options.indexOf(monthVal) !== -1) {
            monthSelect.value = monthVal;
        }
    }
    dateInput.addEventListener('change', setDefaultMonth);
});
</script>
@endpush

@endsection
