@if($expensesByPaymentMethod->isNotEmpty())
<div class="widget-content">
    <div class="mx-auto max-w-[140px]">
        <canvas class="widget-chart" data-chart="expenses_payment_method" height="50"></canvas>
    </div>
</div>
@else
<div class="widget-content">
    <p class="py-6 text-center text-slate-500 dark:text-slate-400">No hay gastos en este periodo</p>
</div>
@endif

