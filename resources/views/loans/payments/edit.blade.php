@extends('layouts.app')

@section('title', 'Editar pago')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <nav class="text-xs text-slate-500 mb-1">
                    <a href="{{ route('loans.index') }}" class="text-brand-600 hover:underline">Préstamos</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('loans.show', $loan) }}" class="text-brand-600 hover:underline">{{ $loan->name }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700">Editar pago</span>
                </nav>
                <h1 class="text-lg font-semibold">Editar pago</h1>
            </div>
            <a href="{{ route('loans.show', $loan) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100 max-w-md">
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('loans.payments.update', [$loan, $payment]) }}">
            @csrf
            @method('PUT')
            <label class="block mb-4">
                <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                <input type="date" name="date" value="{{ old('date', $payment->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <label class="block mb-4">
                <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
                <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $payment->amount) }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <label class="block mb-4">
                <span class="text-xs font-semibold text-slate-700">Comentario</span>
                <input type="text" name="comment" value="{{ old('comment', $payment->comment) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"/>
            </label>
            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
                <a href="{{ route('loans.show', $loan) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
