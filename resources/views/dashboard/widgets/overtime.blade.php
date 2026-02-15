@if($overtimeByStore->isNotEmpty())
<div class="widget-content">
    <canvas class="widget-chart" data-chart="overtime" height="160"></canvas>
</div>
@else
<div class="widget-content">
    <p class="py-6 text-center text-slate-500 dark:text-slate-400">No hay horas extras en este periodo</p>
</div>
@endif
