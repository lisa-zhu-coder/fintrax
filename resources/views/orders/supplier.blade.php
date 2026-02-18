@extends('layouts.app')

@section('title', 'Pedidos - ' . $supplier->name)

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('orders.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-1 inline-block">← Pedidos</a>
                <h1 class="text-lg font-semibold">{{ $supplier->name }}</h1>
                <p class="text-sm text-slate-500">
                    @if($supplier->type)
                        {{ \App\Models\Supplier::TYPES[$supplier->type] ?? ucfirst(str_replace('_', ' ', $supplier->type)) }}
                    @else
                        Pedidos de este proveedor
                    @endif
                </p>
            </div>
            @if(auth()->user()->hasPermission('orders.main.create'))
            <a href="{{ route('orders.create') }}?supplier_id={{ $supplier->id }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Añadir pedido
                </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Resumen por tienda (primero, según especificación) -->
    @if(!empty($summaryByStore))
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100 mt-10">
        <h2 class="mb-4 text-base font-semibold">Resumen por tienda</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2 text-right">Pedidos</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Pagado</th>
                        <th class="px-3 py-2 text-right">Pendiente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($summaryByStore as $storeSummary)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-semibold">{{ $storeSummary['store_name'] }}</td>
                            <td class="px-3 py-2 text-right">{{ $storeSummary['total_orders'] }}</td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ number_format($storeSummary['total_amount'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right text-emerald-700 whitespace-nowrap">{{ number_format($storeSummary['total_paid'], 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $storeSummary['total_pending'] > 0 ? 'text-amber-700' : 'text-emerald-700' }} whitespace-nowrap">
                                {{ number_format($storeSummary['total_pending'], 2, ',', '.') }} €
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Listado de pedidos -->
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100 mt-10">
        <h2 class="mb-4 text-base font-semibold">Pedidos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Tienda</th>
                        <th class="px-3 py-2">Nº Factura</th>
                        <th class="px-3 py-2">Nº Pedido</th>
                        <th class="px-3 py-2">Concepto</th>
                        <th class="px-3 py-2 text-right">Importe</th>
                        <th class="px-3 py-2 text-right">Pendiente</th>
                        <th class="px-3 py-2">Factura</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $order->status === 'pagado' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} ring-1">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ $order->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">{{ $order->store->name }}</td>
                            <td class="px-3 py-2">{{ $order->invoice_number ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $order->order_number }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                    {{ ucfirst($order->concept) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">{{ number_format($order->amount, 2, ',', '.') }} €</td>
                            <td class="px-3 py-2 text-right font-semibold {{ ($order->amount - $order->total_paid) > 0 ? 'text-amber-700' : 'text-emerald-700' }} whitespace-nowrap">
                                {{ number_format(max(0, $order->amount - $order->total_paid), 2, ',', '.') }} €
                            </td>
                            <td class="px-3 py-2 text-center">
                                @php $invoice = $order->invoice(); @endphp
                                @if($invoice)
                                    @php $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->exists($invoice->file_path) ? \Illuminate\Support\Facades\Storage::disk('local')->mimeType($invoice->file_path) : 'application/octet-stream'; @endphp
                                    <a href="#" data-invoice-preview data-invoice-id="{{ $invoice->id }}"
                                       data-invoice-title="Previsualizar Factura"
                                       data-invoice-subtitle="{{ $invoice->supplier_name ?: 'Sin proveedor' }} - {{ $invoice->invoice_number ?: 'Sin número' }}"
                                       data-invoice-serve="{{ route('invoices.serve', $invoice->id) }}"
                                       data-invoice-download="{{ route('invoices.download', $invoice->id) }}"
                                       data-invoice-mime="{{ $mimeType }}"
                                       class="inline-flex items-center justify-center rounded-lg p-1.5 text-brand-600 hover:bg-brand-50" title="Ver factura">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('orders.show', $order) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ver</a>
                                    @if(auth()->user()->hasPermission('orders.main.edit'))
                                    <a href="{{ route('orders.edit', $order) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">Editar</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('orders.main.delete'))
                                    <form method="POST" action="{{ route('orders.destroy', $order) }}" class="inline" onsubmit="return confirm('¿Estás seguro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Eliminar</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-6 text-center text-slate-500">No hay pedidos para este proveedor</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
