@extends('layouts.app')

@section('title', 'Nuevo préstamo')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('loans.index') }}" class="text-brand-600 hover:underline">Préstamos</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">Nuevo</span>
                </nav>
                <h1 class="text-lg font-semibold">Nuevo préstamo</h1>
            </div>
            <a href="{{ route('loans.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('loans.store') }}" class="space-y-6" id="loanForm">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre *</span>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo de préstamo *</span>
                    <select name="loan_type_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">Selecciona tipo</option>
                        @foreach($loanTypes as $type)
                            <option value="{{ $type->id }}" {{ old('loan_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Dirección *</span>
                    <select name="direction" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="company_owes" {{ old('direction') === 'company_owes' ? 'selected' : '' }}>La empresa debe</option>
                        <option value="company_lends" {{ old('direction') === 'company_lends' ? 'selected' : '' }}>La empresa presta (nos deben)</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Capital / Principal (€) *</span>
                    <input type="number" name="principal_amount" step="0.01" min="0" value="{{ old('principal_amount') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Tipo de interés</span>
                    <select name="interest_type" id="interest_type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">—</option>
                        <option value="fixed" {{ old('interest_type') === 'fixed' ? 'selected' : '' }}>Fijo</option>
                        <option value="variable" {{ old('interest_type') === 'variable' ? 'selected' : '' }}>Variable (índice + diferencial)</option>
                    </select>
                </label>
                <label class="block" id="wrap_interest_rate">
                    <span class="text-xs font-semibold text-slate-700">Tasa interés anual (%)</span>
                    <input type="number" name="interest_rate" step="0.01" min="0" max="100" value="{{ old('interest_rate') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block hidden" id="wrap_interest_index">
                    <span class="text-xs font-semibold text-slate-700">Índice de referencia</span>
                    <select name="interest_index" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
                        <option value="">—</option>
                        @foreach($interestIndexOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('interest_index') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block hidden" id="wrap_interest_spread">
                    <span class="text-xs font-semibold text-slate-700">Diferencial (%)</span>
                    <input type="number" name="interest_spread" step="0.01" min="0" value="{{ old('interest_spread') }}" placeholder="ej. 1.2" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block hidden" id="wrap_interest_floor">
                    <span class="text-xs font-semibold text-slate-700">Suelo (%)</span>
                    <input type="number" name="interest_floor" step="0.01" min="0" value="{{ old('interest_floor') }}" placeholder="ej. 0.5" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block hidden" id="wrap_interest_cap">
                    <span class="text-xs font-semibold text-slate-700">Techo (%)</span>
                    <input type="number" name="interest_cap" step="0.01" min="0" value="{{ old('interest_cap') }}" placeholder="opcional" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block hidden" id="wrap_initial_index_rate">
                    <span class="text-xs font-semibold text-slate-700">Tasa índice inicial (%)</span>
                    <input type="number" name="initial_index_rate" step="0.01" min="0" value="{{ old('initial_index_rate') }}" placeholder="Para generar la tabla de amortización" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Plazo (meses)</span>
                    <input type="number" name="term_months" min="1" value="{{ old('term_months') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha inicio</span>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Comisión apertura (€)</span>
                    <input type="number" name="opening_fee" step="0.01" min="0" value="{{ old('opening_fee') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
                </label>
            </div>
            <input type="hidden" name="settlement_frequency" value="monthly"/>
            <input type="hidden" name="amortization_system" value="french"/>
            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Crear préstamo</button>
                <a href="{{ route('loans.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('interest_type');
    var wrapRate = document.getElementById('wrap_interest_rate');
    var wrapIndex = document.getElementById('wrap_interest_index');
    var wrapSpread = document.getElementById('wrap_interest_spread');
    var wrapFloor = document.getElementById('wrap_interest_floor');
    var wrapCap = document.getElementById('wrap_interest_cap');
    var wrapInitial = document.getElementById('wrap_initial_index_rate');
    function toggle() {
        var v = typeSelect.value;
        wrapRate.classList.toggle('hidden', v === 'variable');
        wrapIndex.classList.toggle('hidden', v !== 'variable');
        wrapSpread.classList.toggle('hidden', v !== 'variable');
        wrapFloor.classList.toggle('hidden', v !== 'variable');
        wrapCap.classList.toggle('hidden', v !== 'variable');
        wrapInitial.classList.toggle('hidden', v !== 'variable');
    }
    typeSelect.addEventListener('change', toggle);
    toggle();
});
</script>
@endsection
