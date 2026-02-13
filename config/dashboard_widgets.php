<?php

/**
 * Definición de widgets del dashboard.
 * key, name, component (vista Blade), default_width, default_height, resizable, movable
 */
return [
    'ultimos_registros' => [
        'name' => 'Últimos registros',
        'component' => 'dashboard.widgets.records',
        'default_width' => 6,
        'default_height' => 3,
        'resizable' => true,
        'movable' => true,
        'permission' => 'dashboard.records.view',
    ],
    'gastos_por_categoria' => [
        'name' => 'Gastos por categoría',
        'component' => 'dashboard.widgets.expenses',
        'default_width' => 6,
        'default_height' => 2,
        'resizable' => true,
        'movable' => true,
        'permission' => 'dashboard.expenses.view',
    ],
    'ingresos_por_metodo' => [
        'name' => 'Ingresos por método de pago',
        'component' => 'dashboard.widgets.income',
        'default_width' => 6,
        'default_height' => 2,
        'resizable' => true,
        'movable' => true,
        'permission' => 'dashboard.income.view',
    ],
    'pedidos_pagado_vs_pendiente' => [
        'name' => 'Pedidos: Pagado vs Pendiente',
        'component' => 'dashboard.widgets.orders',
        'default_width' => 4,
        'default_height' => 2,
        'resizable' => true,
        'movable' => true,
        'permission' => 'dashboard.orders.view',
    ],
    'horas_extras_rrhh' => [
        'name' => 'Horas extra y festivos',
        'component' => 'dashboard.widgets.overtime',
        'default_width' => 4,
        'default_height' => 2,
        'resizable' => true,
        'movable' => true,
        'permission' => 'dashboard.overtime.view',
    ],
    'evolucion_ventas' => [
        'name' => 'Evolución de ventas y gastos',
        'component' => 'dashboard.widgets.sales',
        'default_width' => 12,
        'default_height' => 2,
        'resizable' => true,
        'movable' => true,
        'permission' => 'dashboard.chart.view',
    ],
    'accesos_rapidos' => [
        'name' => 'Accesos rápidos',
        'component' => 'dashboard.widgets.quick_actions',
        'default_width' => 12,
        'default_height' => 1,
        'resizable' => true,
        'movable' => true,
        'permission' => null, // varios: financial.daily_closes.create, treasury.cash_control.view, etc.
        'permission_any' => [
            'financial.daily_closes.create',
            'treasury.cash_control.view',
            'treasury.cash_wallets.create',
            'orders.main.create',
        ],
    ],
];
