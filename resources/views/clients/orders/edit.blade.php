@extends('layouts.app')

@section('title', 'Editar pedido cliente - ' . $store->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div>
            <a href="{{ route('clients.orders.store', $store) }}" class="text-sm text-slate-500 hover:text-brand-600 mb-1 inline-block">← Pedidos clientes — {{ $store->name }}</a>
            <h1 class="text-lg font-semibold">Editar pedido de cliente</h1>
            <p class="text-sm text-slate-500">Tienda: {{ $store->name }}</p>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <form method="POST" action="{{ route('clients.orders.update', [$store, $order]) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Fecha *</span>
                    <input type="date" name="date" value="{{ old('date', $order->date->format('Y-m-d')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('date') border-rose-300 @enderror">
                    @error('date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Estado *</span>
                    <select name="status" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('status') border-rose-300 @enderror">
                        @foreach(\App\Models\CustomerOrder::statuses() as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $order->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </label>
            </div>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Cliente *</span>
                <input type="text" name="client_name" value="{{ old('client_name', $order->client_name) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('client_name') border-rose-300 @enderror">
                @error('client_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Teléfono</span>
                <input type="text" name="phone" value="{{ old('phone', $order->phone) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('phone') border-rose-300 @enderror">
                @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </label>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">Artículo</span>
                    <input type="text" name="article" value="{{ old('article', $order->article) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('article') border-rose-300 @enderror">
                    @error('article') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-700">SKU</span>
                    <input type="text" name="sku" value="{{ old('sku', $order->sku) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('sku') border-rose-300 @enderror">
                    @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </label>
            </div>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Fecha aviso</span>
                <input type="date" name="notification_date" value="{{ old('notification_date', $order->notification_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('notification_date') border-rose-300 @enderror">
                @error('notification_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-slate-700">Notas</span>
                <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm @error('notes') border-rose-300 @enderror">{{ old('notes', $order->notes) }}</textarea>
                @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </label>
            <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
                <a href="{{ route('clients.orders.store', $store) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
