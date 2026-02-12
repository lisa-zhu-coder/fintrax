@extends('layouts.app')

@section('title', 'Detalle de Pedido')

@section('content')
<div class="space-y-6">
    <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Detalles del pedido</h1>
                <p class="text-sm text-slate-500">Información completa del pedido</p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->hasPermission('orders.main.edit'))
                <a href="{{ route('orders.edit', $order) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Editar
                </a>
                @endif
                @if($order->supplier_id)
                <a href="{{ route('orders.supplier', $order->supplier) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    ← Volver a {{ $order->supplier->name }}
                </a>
                @else
                <a href="{{ route('orders.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    ← Volver
                </a>
                @endif
            </div>
        </div>
    </header>

    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs font-semibold text-slate-700">Fecha</span>
                    <div class="mt-1 text-sm text-slate-900">{{ $order->date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-700">Tienda</span>
                    <div class="mt-1 text-sm text-slate-900">{{ $order->store->name }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-700">Número de factura</span>
                    <div class="mt-1 text-sm text-slate-900">{{ $order->invoice_number }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-700">Número de pedido</span>
                    <div class="mt-1 text-sm text-slate-900">{{ $order->order_number }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-700">Concepto</span>
                    <div class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                            {{ ucfirst($order->concept) }}
                        </span>
                    </div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-700">Estado</span>
                    <div class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $order->status === 'pagado' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} ring-1">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-700">Importe total</span>
                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($order->amount, 2, ',', '.') }} €</div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-700">Importe pendiente</span>
                    <div class="mt-1 text-sm font-semibold {{ $order->pending_amount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                        {{ number_format($order->pending_amount, 2, ',', '.') }} €
                    </div>
                </div>
            </div>

            @php
                $invoice = $order->invoice();
            @endphp
            @if($invoice)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Factura asociada</h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs font-semibold text-slate-500">Proveedor</span>
                            <div class="mt-1 text-sm text-slate-900">{{ $invoice->supplier_name }}</div>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-500">Fecha</span>
                            <div class="mt-1 text-sm text-slate-900">{{ $invoice->date->format('d/m/Y') }}</div>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-500">Importe</span>
                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($invoice->total_amount, 2, ',', '.') }} €</div>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-500">Estado</span>
                            <div class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $invoice->status === 'pagada' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} ring-1">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @if($invoice->file_path)
                    @php
                        $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->exists($invoice->file_path) 
                            ? \Illuminate\Support\Facades\Storage::disk('local')->mimeType($invoice->file_path) 
                            : 'application/octet-stream';
                    @endphp
                    <div class="pt-2">
                        <a href="#" 
                           data-invoice-preview 
                           data-invoice-id="{{ $invoice->id }}"
                           data-invoice-title="Previsualizar Factura"
                           data-invoice-subtitle="{{ $invoice->supplier_name ?: 'Sin proveedor' }} - {{ $invoice->invoice_number ?: 'Sin número' }}"
                           data-invoice-serve="{{ route('invoices.serve', $invoice->id) }}"
                           data-invoice-download="{{ route('invoices.download', $invoice->id) }}"
                           data-invoice-mime="{{ $mimeType }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Ver factura
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Pagos registrados</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2 text-left">Fecha</th>
                                <th class="px-3 py-2 text-left">Forma de pago</th>
                                <th class="px-3 py-2 text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $payment)
                                <tr class="border-b border-slate-100">
                                    <td class="px-3 py-2 text-sm text-slate-600">{{ $payment->date->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-sm text-slate-600">
                                        @php
                                            $methods = ['cash' => 'Efectivo', 'bank' => 'Banco', 'transfer' => 'Transferencia', 'card' => 'Tarjeta'];
                                        @endphp
                                        {{ $methods[$payment->method] ?? $payment->method }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right font-semibold text-slate-900">{{ number_format($payment->amount, 2, ',', '.') }} €</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-center text-slate-500 text-sm">No hay pagos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-white">
                            <tr>
                                <td colspan="2" class="px-3 py-2 text-sm font-semibold text-slate-900">Total pagado:</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold text-emerald-700">{{ number_format($order->total_paid, 2, ',', '.') }} €</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($order->history && count($order->history) > 0)
            <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-4 ring-1 ring-slate-200">
                <div class="mb-3 flex items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-700">
                        <path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span class="text-sm font-semibold text-slate-900">Historial de cambios</span>
                </div>
                <div class="space-y-2 text-xs">
                    @php
                        $fieldLabels = [
                            'date' => 'Fecha',
                            'storeId' => 'Tienda',
                            'invoiceNumber' => 'Número de factura',
                            'orderNumber' => 'Número de pedido',
                            'concept' => 'Concepto',
                            'amount' => 'Importe',
                            'payments' => 'Pagos'
                        ];
                        $actionLabels = [
                            'created' => 'Creado',
                            'updated' => 'Modificado',
                            'payment_added' => 'Pago añadido',
                            'payment_removed' => 'Pago eliminado',
                            'payment_modified' => 'Pago modificado',
                            'deleted' => 'Eliminado'
                        ];
                        $conceptLabels = ['pedido' => 'Pedido', 'royalty' => 'Royalty', 'rectificacion' => 'Rectificación', 'tara' => 'Tara'];
                        $paymentMethods = ['cash' => 'Efectivo', 'bank' => 'Banco', 'transfer' => 'Transferencia', 'card' => 'Tarjeta'];
                        $sortedHistory = collect($order->history)->sortByDesc(function($item) {
                            return is_string($item['timestamp']) ? strtotime($item['timestamp']) : $item['timestamp'];
                        });
                    @endphp
                    @foreach($sortedHistory as $item)
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-slate-900">{{ $actionLabels[$item['action']] ?? $item['action'] }}</span>
                                <span class="text-xs text-slate-500">por {{ $item['user_name'] ?? 'Usuario desconocido' }}</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                @php
                                    $timestamp = is_string($item['timestamp']) ? strtotime($item['timestamp']) : $item['timestamp'];
                                    echo date('d/m/Y H:i', $timestamp / 1000);
                                @endphp
                            </div>
                            @if(isset($item['changes']) && is_array($item['changes']) && count($item['changes']) > 0)
                                <div class="mt-2 space-y-1">
                                    @foreach($item['changes'] as $field => $change)
                                        @php
                                            $label = $fieldLabels[$field] ?? $field;
                                            $oldVal = '—';
                                            $newVal = '—';
                                            
                                            if($field === 'payments') {
                                                $oldVal = ($change['old'] ?? 0) . ' pago(s)';
                                                $newVal = ($change['new'] ?? 0) . ' pago(s)';
                                            } elseif($field === 'storeId') {
                                                $oldStore = $stores->find($change['old'] ?? null);
                                                $newStore = $stores->find($change['new'] ?? null);
                                                $oldVal = $oldStore ? $oldStore->name : ($change['old'] ?? '—');
                                                $newVal = $newStore ? $newStore->name : ($change['new'] ?? '—');
                                            } elseif($field === 'concept') {
                                                $oldVal = $conceptLabels[$change['old'] ?? ''] ?? ($change['old'] ?? '—');
                                                $newVal = $conceptLabels[$change['new'] ?? ''] ?? ($change['new'] ?? '—');
                                            } elseif($field === 'amount') {
                                                $oldVal = number_format($change['old'] ?? 0, 2, ',', '.') . ' €';
                                                $newVal = number_format($change['new'] ?? 0, 2, ',', '.') . ' €';
                                            } else {
                                                $oldVal = $change['old'] ?? '—';
                                                $newVal = $change['new'] ?? '—';
                                            }
                                        @endphp
                                        <div class="text-xs text-slate-600">
                                            <span class="font-medium">{{ $label }}:</span>
                                            <span class="text-rose-600 line-through">{{ $oldVal }}</span>
                                            <span class="mx-1">→</span>
                                            <span class="text-emerald-600 font-medium">{{ $newVal }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
