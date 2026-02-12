@extends('layouts.app')

@section('title', 'Reducción de Efectivo por Tienda')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Reducción de Efectivo por Tienda</h1>
                <p class="text-sm text-slate-500">Configura el porcentaje de reducción de efectivo aplicado a cada tienda</p>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('store-cash-reductions.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Tienda</th>
                            <th class="px-3 py-3 text-right">% Reducción efectivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($storesWithReduction as $store)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3">
                                    <div class="font-semibold">{{ $store['name'] }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <input 
                                            type="number" 
                                            name="reductions[{{ $loop->index }}][cash_reduction_percent]" 
                                            value="{{ number_format($store['cash_reduction_percent'], 2, '.', '') }}" 
                                            min="0" 
                                            max="100" 
                                            step="0.01"
                                            class="w-24 rounded-xl border border-slate-200 bg-white px-3 py-2 text-right text-sm outline-none ring-brand-200 focus:ring-4"
                                            placeholder="0.00"
                                        />
                                        <span class="text-sm text-slate-500">%</span>
                                        <input type="hidden" name="reductions[{{ $loop->index }}][store_id]" value="{{ $store['id'] }}">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(count($storesWithReduction) === 0)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                    No hay tiendas disponibles
                </div>
            @endif

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17 21v-8H7v8M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    @if($errors->any())
        <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-100">
            <p class="font-semibold mb-2">Errores:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
