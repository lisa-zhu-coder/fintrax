@extends('layouts.app')

@section('title', 'Editar Cartera de Efectivo')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Editar Cartera de Efectivo</h1>
                <p class="text-sm text-slate-500">Modifica los datos de la cartera</p>
            </div>
            <a href="{{ route('cash-wallets.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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

        <form method="POST" action="{{ route('cash-wallets.update', $cashWallet) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Nombre de la cartera *</span>
                    <input type="text" name="name" value="{{ old('name', $cashWallet->name) }}" required maxlength="255" class="mt-1 w-full rounded-xl border {{ $errors->has('name') ? 'border-rose-400' : 'border-slate-200' }} bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4" placeholder="Ej: Caja principal"/>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('cash-wallets.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
