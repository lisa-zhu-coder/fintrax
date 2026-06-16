@php
    $alignClass = match($column['align'] ?? 'left') {
        'right' => 'text-right',
        'center' => 'text-center',
        default => '',
    };
    $nowrapClass = in_array($column['key'], ['amount', 'total_paid', 'pending', 'total_amount'], true) ? 'whitespace-nowrap' : '';
@endphp
<td class="px-3 py-2 {{ $alignClass }} {{ $nowrapClass }}">
@switch($column['key'])
    @case('supplier')
        <a href="{{ route('orders.supplier', $row['supplier']) }}" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">
            {{ $row['supplier']->name }}
        </a>
        @break
    @case('type')
        @if($row['supplier']->type)
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700">
                {{ \App\Models\Supplier::TYPES[$row['supplier']->type] ?? ucfirst(str_replace('_', ' ', $row['supplier']->type)) }}
            </span>
        @else
            <span class="text-slate-400">—</span>
        @endif
        @break
    @case('total_orders')
        {{ $row['total_orders'] }}
        @break
    @case('total_amount')
        <span class="font-semibold">{{ number_format($row['total_amount'], 2, ',', '.') }} €</span>
        @break
    @case('total_paid')
        <span class="text-emerald-700">{{ number_format($row['total_paid'], 2, ',', '.') }} €</span>
        @break
    @case('total_pending')
        <span class="font-semibold {{ $row['total_pending'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
            {{ number_format($row['total_pending'], 2, ',', '.') }} €
        </span>
        @break
    @case('actions')
        <a href="{{ route('orders.supplier', $row['supplier']) }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            Ver pedidos
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
        @break
@endswitch
</td>
