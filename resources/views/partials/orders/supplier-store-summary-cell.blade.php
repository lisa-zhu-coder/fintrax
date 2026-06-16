@php
    $alignClass = match($column['align'] ?? 'left') {
        'right' => 'text-right',
        'center' => 'text-center',
        default => '',
    };
@endphp
<td class="px-3 py-2 {{ $alignClass }} {{ in_array($column['key'], ['total_amount', 'total_paid', 'total_pending'], true) ? 'whitespace-nowrap' : '' }} {{ $column['key'] === 'store_name' ? 'font-semibold' : '' }}">
@switch($column['key'])
    @case('store_name')
        {{ $storeSummary['store_name'] }}
        @break
    @case('total_orders')
        {{ $storeSummary['total_orders'] }}
        @break
    @case('total_amount')
        <span class="font-semibold">{{ number_format($storeSummary['total_amount'], 2, ',', '.') }} €</span>
        @break
    @case('total_paid')
        <span class="text-emerald-700">{{ number_format($storeSummary['total_paid'], 2, ',', '.') }} €</span>
        @break
    @case('total_pending')
        <span class="font-semibold {{ $storeSummary['total_pending'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
            {{ number_format($storeSummary['total_pending'], 2, ',', '.') }} €
        </span>
        @break
@endswitch
</td>
