@if(count($chartData['labels']) > 0)
<div class="widget-content">
    <canvas class="widget-chart" data-chart="sales" height="100"></canvas>
</div>
@else
<div class="widget-content">
    <p class="py-6 text-center text-slate-500">No hay datos en este periodo</p>
</div>
@endif
