@php
    $vatOptions = \App\Models\CompanyBusiness::vatRateOptions();
    $selectedRate = (string) (old('vat_rate', $value ?? 21));
@endphp
<label class="block {{ $class ?? '' }}">
    <span class="text-xs font-semibold text-slate-700">Tipo de IVA</span>
    <select name="vat_rate" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4">
        @foreach($vatOptions as $rate => $label)
            <option value="{{ $rate }}" @selected($selectedRate === (string) $rate)>{{ $label }}</option>
        @endforeach
    </select>
    <span class="mt-1 block text-xs text-slate-500">Se usa en ventas declaradas para calcular el importe sin IVA de este negocio.</span>
</label>
