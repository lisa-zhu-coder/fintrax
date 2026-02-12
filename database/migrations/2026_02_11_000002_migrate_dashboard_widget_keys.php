<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEY_MAP = [
        'income' => 'ingresos_por_metodo',
        'expenses' => 'gastos_por_categoria',
        'orders' => 'pedidos_pagado_vs_pendiente',
        'overtime' => 'horas_extras_rrhh',
        'records' => 'ultimos_registros',
        'sales' => 'evolucion_ventas',
        'quick_actions' => 'accesos_rapidos',
    ];

    public function up(): void
    {
        foreach (self::KEY_MAP as $old => $new) {
            DB::table('dashboard_widgets')
                ->where('widget_key', $old)
                ->update(['widget_key' => $new]);
        }
    }

    public function down(): void
    {
        $reverse = array_flip(self::KEY_MAP);
        foreach ($reverse as $new => $old) {
            DB::table('dashboard_widgets')
                ->where('widget_key', $new)
                ->update(['widget_key' => $old]);
        }
    }
};
