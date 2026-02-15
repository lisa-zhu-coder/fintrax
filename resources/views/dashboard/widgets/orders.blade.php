@php
    $paid = $ordersPaidVsPending['paid'] ?? 0;
    $pending = $ordersPaidVsPending['pending'] ?? 0;
    $total = $paid + $pending;
@endphp
<div class="widget-content">
    @if($total > 0)
    <div class="mx-auto max-w-[140px]">
        <canvas class="widget-chart" data-chart="orders" height="50"></canvas>
    </div>
    @else
    <p class="py-6 text-center text-slate-500 dark:text-slate-400">No hay pedidos en este periodo</p>
    @endif
</div>
