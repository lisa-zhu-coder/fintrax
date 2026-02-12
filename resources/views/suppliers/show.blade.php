@extends('layouts.app')

@section('title', 'Proveedor: ' . $supplier->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">{{ $supplier->name }}</h1>
                <p class="text-sm text-slate-500">Pedidos asociados a este proveedor</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('orders.index') }}?supplier={{ $supplier->id }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver pedidos</a>
                @if(auth()->user()->hasPermission('admin.suppliers.edit'))
                <a href="{{ route('suppliers.edit', $supplier) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Editar</a>
                @endif
                <a href="{{ route('suppliers.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Volver</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Datos del proveedor</h2>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <span class="text-xs font-semibold text-slate-500">CIF</span>
                <p class="text-sm font-medium text-slate-800">{{ $supplier->cif ?? '—' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <span class="text-xs font-semibold text-slate-500">Email</span>
                <p class="text-sm font-medium text-slate-800">{{ $supplier->email ?? '—' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2 md:col-span-2">
                <span class="text-xs font-semibold text-slate-500">Dirección</span>
                <p class="text-sm font-medium text-slate-800">{{ $supplier->address ?? '—' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2">
                <span class="text-xs font-semibold text-slate-500">Teléfono</span>
                <p class="text-sm font-medium text-slate-800">{{ $supplier->phone ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <h2 class="mb-4 text-base font-semibold">Últimos pedidos</h2>
        @if($supplier->orders->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2">Factura</th>
                        <th class="px-3 py-2 text-right">Importe</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($supplier->orders->take(20) as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2">{{ $order->date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">{{ $order->store?->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $order->invoice_number }}</td>
                        <td class="px-3 py-2 text-right font-semibold">{{ number_format($order->amount, 2, ',', '.') }} €</td>
                        <td class="px-3 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $order->status === 'pagado' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <a href="{{ route('orders.show', $order) }}" class="text-brand-600 hover:underline">Ver</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($supplier->orders->count() > 20)
        <p class="mt-3 text-sm text-slate-500">Mostrando 20 de {{ $supplier->orders->count() }} pedidos. <a href="{{ route('orders.index') }}?supplier={{ $supplier->id }}" class="text-brand-600 hover:underline">Ver todos</a></p>
        @endif
        @else
        <p class="py-6 text-center text-slate-500">No hay pedidos asociados</p>
        @endif
    </div>
</div>
@endsection
