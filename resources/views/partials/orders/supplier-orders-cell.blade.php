@php
    $alignClass = match($column['align'] ?? 'left') {
        'right' => 'text-right',
        'center' => 'text-center',
        default => '',
    };
    $nowrapClass = in_array($column['key'], ['amount', 'total_paid', 'pending'], true) ? 'whitespace-nowrap' : '';
@endphp
<td class="px-3 py-2 {{ $alignClass }} {{ $nowrapClass }}">
@switch($column['key'])
    @case('status')
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $order->status === 'pagado' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }} ring-1">
            {{ ucfirst($order->status) }}
        </span>
        @break
    @case('date')
        {{ $order->date->format('d/m/Y') }}
        @break
    @case('store')
        {{ $order->store->name }}
        @break
    @case('split_type')
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $order->splitTypeLabel() === 'Conjunto' ? 'bg-violet-50 text-violet-700 ring-1 ring-violet-100' : 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' }}">
            {{ $order->splitTypeLabel() }}
        </span>
        @break
    @case('origin_store')
        {{ $originStoresById[$order->originStoreId()] ?? '—' }}
        @break
    @case('invoice_number')
        {{ $order->invoice_number ?? '—' }}
        @break
    @case('order_number')
        {{ $order->order_number }}
        @break
    @case('concept')
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold bg-brand-50 text-brand-700 ring-1 ring-brand-100">
            {{ ucfirst($order->concept) }}
        </span>
        @break
    @case('amount')
        <span class="font-semibold">{{ number_format($order->amount, 2, ',', '.') }} €</span>
        @break
    @case('total_paid')
        <span class="font-semibold text-emerald-700">{{ number_format($order->total_paid, 2, ',', '.') }} €</span>
        @break
    @case('pending')
        <span class="font-semibold {{ $order->pending_amount != 0 ? 'text-amber-700' : 'text-emerald-700' }}">
            {{ number_format($order->pending_amount, 2, ',', '.') }} €
        </span>
        @break
    @case('payment_methods')
        @php
            $methods = $order->payments->pluck('method')->unique()->map(function ($m) {
                return match($m) {
                    'cash' => 'Efectivo',
                    'transfer' => 'Transferencia',
                    'card' => 'Tarjeta',
                    'bank' => 'Transferencia',
                    default => ucfirst($m ?? '—'),
                };
            })->unique()->values()->implode(', ');
        @endphp
        <span class="text-slate-600">{{ $methods ?: '—' }}</span>
        @break
    @case('actions')
        <div class="flex items-center justify-center gap-1">
            @php $invoice = $order->invoice(); @endphp
            @if($invoice)
                @php $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->exists($invoice->file_path) ? \Illuminate\Support\Facades\Storage::disk('local')->mimeType($invoice->file_path) : 'application/octet-stream'; @endphp
                <a href="#" data-invoice-preview data-invoice-id="{{ $invoice->id }}"
                   data-invoice-title="Previsualizar Factura"
                   data-invoice-subtitle="{{ $invoice->supplier_name ?: 'Sin proveedor' }} - {{ $invoice->invoice_number ?: 'Sin número' }}"
                   data-invoice-serve="{{ route('invoices.serve', $invoice->id) }}"
                   data-invoice-download="{{ route('invoices.download', $invoice->id) }}"
                   data-invoice-mime="{{ $mimeType }}"
                   class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600" title="Ver factura">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @else
                <span class="inline-flex w-8 justify-center text-slate-300">—</span>
            @endif
            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600" title="Ver">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            @if(auth()->user()->hasPermission('orders.main.edit'))
            <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-brand-50 hover:text-brand-600" title="Editar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke-linecap="round" stroke-linejoin="round"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            @endif
            @if(auth()->user()->hasPermission('orders.main.delete'))
            <form method="POST" action="{{ route('orders.destroy', $order) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="Eliminar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke-linecap="round" stroke-linejoin="round"/><path d="m10 11 6 6m0-6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>
            @endif
        </div>
        @break
@endswitch
</td>
