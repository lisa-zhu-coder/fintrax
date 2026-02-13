@extends('layouts.app')

@section('title', 'Cierre de caja')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <h1 class="text-lg font-semibold">Cierre de caja</h1>
            <p class="text-sm text-slate-500">Configura los nombres del apartado POS y activa o desactiva la sección de vales en los cierres diarios (empresa {{ $company->name }}).</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('daily-close-settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-800">Nombres del apartado POS</h2>
                <p class="text-xs text-slate-500">Estos textos se muestran en el formulario de cierre diario (crear y editar).</p>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Nombre general del apartado POS</span>
                    <input type="text" name="daily_close_pos_label" value="{{ old('daily_close_pos_label', $company->daily_close_pos_label ?? 'Sistema POS') }}" maxlength="100"
                        class="mt-1 w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Sistema POS">
                    @error('daily_close_pos_label')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Nombre del campo Efectivo</span>
                    <input type="text" name="daily_close_pos_cash_label" value="{{ old('daily_close_pos_cash_label', $company->daily_close_pos_cash_label ?? 'Sistema POS · Efectivo (€)') }}" maxlength="150"
                        class="mt-1 w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Sistema POS · Efectivo (€)">
                    @error('daily_close_pos_cash_label')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Nombre del campo Tarjeta</span>
                    <input type="text" name="daily_close_pos_card_label" value="{{ old('daily_close_pos_card_label', $company->daily_close_pos_card_label ?? 'Sistema POS · Tarjeta (€)') }}" maxlength="150"
                        class="mt-1 w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                        placeholder="Sistema POS · Tarjeta (€)">
                    @error('daily_close_pos_card_label')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <div class="border-t border-slate-200 pt-6">
                <h2 class="text-sm font-semibold text-slate-800">Sección Vales</h2>
                <p class="mt-1 text-xs text-slate-500">Activa o desactiva el bloque «Vales» (entrada, salida, resultado) en el formulario de cierre diario.</p>
                <div class="mt-4 flex items-center gap-3">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="hidden" name="daily_close_vouchers_enabled" value="0">
                        <input type="checkbox" name="daily_close_vouchers_enabled" value="1" {{ old('daily_close_vouchers_enabled', $company->daily_close_vouchers_enabled ?? true) ? 'checked' : '' }}
                            class="peer sr-only">
                        <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-brand-200"></div>
                        <span class="ml-3 text-sm font-medium text-slate-700">Mostrar apartado de vales en cierres diarios</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
