@if($expensesByCategory->isNotEmpty())
<div class="widget-content">
    <canvas class="widget-chart cursor-pointer" data-chart="expenses" height="120"></canvas>
</div>
@else
<div class="widget-content">
    <p class="py-6 text-center text-slate-500">No hay gastos registrados en este periodo</p>
</div>
@endif
