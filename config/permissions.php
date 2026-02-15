<?php

/**
 * Definición de permisos Fintrax: modulo.submodulo.accion
 * Fuente única para UI de roles, seeders y validación en controladores.
 */

return [
    'action_labels' => [
        'view' => 'Ver',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'export' => 'Exportar',
        'configure' => 'Configurar',
        'view_own' => 'Solo su ficha',
        'view_store' => 'Todas las fichas de la tienda',
    ],

    'modules' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'items' => [
                ['key' => 'main', 'label' => 'Acceso al Dashboard', 'actions' => ['view']],
                ['key' => 'income', 'label' => 'Tarjeta / Gráfica de Ingresos', 'actions' => ['view']],
                ['key' => 'expenses', 'label' => 'Tarjeta / Gráfica de Gastos', 'actions' => ['view']],
                ['key' => 'profit', 'label' => 'Tarjeta de Beneficio', 'actions' => ['view']],
                ['key' => 'chart', 'label' => 'Gráfica evolución ventas y gastos', 'actions' => ['view']],
                ['key' => 'records', 'label' => 'Registros recientes', 'actions' => ['view']],
                ['key' => 'orders', 'label' => 'Gráfica Pedidos (Pagado vs Pendiente)', 'actions' => ['view']],
                ['key' => 'overtime', 'label' => 'Gráfica Horas extra y festivos', 'actions' => ['view']],
                ['key' => 'quick_actions', 'label' => 'Accesos rápidos', 'actions' => ['view']],
            ],
        ],
        [
            'key' => 'financial',
            'label' => 'Finanzas',
            'items' => [
                ['key' => 'registros', 'label' => 'Registros', 'actions' => ['view', 'create', 'edit', 'delete', 'export']],
                ['key' => 'income', 'label' => 'Ingresos', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'expenses', 'label' => 'Gastos', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'daily_closes', 'label' => 'Cierres diarios', 'actions' => ['view', 'create', 'edit', 'delete']],
            ],
        ],
        [
            'key' => 'treasury',
            'label' => 'Tesorería',
            'items' => [
                ['key' => 'cash_control', 'label' => 'Control de efectivo', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'cash_wallets', 'label' => 'Carteras / monederos', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'bank_control', 'label' => 'Control de banco', 'actions' => ['view', 'edit']],
                ['key' => 'bank_conciliation', 'label' => 'Conciliación bancaria', 'actions' => ['view', 'edit', 'delete']],
                ['key' => 'transfers', 'label' => 'Traspasos', 'actions' => ['view', 'create', 'edit', 'delete']],
            ],
        ],
        [
            'key' => 'objectives',
            'label' => 'Objetivos mensuales',
            'items' => [
                ['key' => 'main', 'label' => 'Objetivos', 'actions' => ['view', 'create', 'edit', 'delete', 'export']],
            ],
        ],
        [
            'key' => 'declared_sales',
            'label' => 'Ventas declaradas',
            'items' => [
                ['key' => 'main', 'label' => 'Ventas declaradas', 'actions' => ['view', 'create']],
            ],
        ],
        [
            'key' => 'invoices',
            'label' => 'Facturas',
            'items' => [
                ['key' => 'main', 'label' => 'Facturas', 'actions' => ['view', 'create', 'edit', 'delete', 'export']],
            ],
        ],
        [
            'key' => 'orders',
            'label' => 'Pedidos',
            'items' => [
                ['key' => 'main', 'label' => 'Pedidos', 'actions' => ['view', 'create', 'edit', 'delete']],
            ],
        ],
        [
            'key' => 'clients',
            'label' => 'Clientes',
            'items' => [
                ['key' => 'orders', 'label' => 'Pedidos clientes', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'repairs', 'label' => 'Reparaciones', 'actions' => ['view', 'create', 'edit', 'delete']],
            ],
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventario',
            'items' => [
                ['key' => 'rings', 'label' => 'Inventario de anillos', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'generic', 'label' => 'Inventarios genéricos', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'products', 'label' => 'Inventario de productos', 'actions' => ['view', 'create', 'edit', 'delete']],
            ],
        ],
        [
            'key' => 'sales',
            'label' => 'Venta de productos',
            'items' => [
                ['key' => 'products', 'label' => 'Cantidad vendida por producto', 'actions' => ['view', 'create', 'edit']],
            ],
        ],
        [
            'key' => 'hr',
            'label' => 'RR.HH.',
            'items' => [
                ['key' => 'employees', 'label' => 'Empleados', 'actions' => ['view_own', 'view_store', 'create', 'edit', 'delete', 'configure']],
                ['key' => 'overtime', 'label' => 'Horas extras', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'vacations', 'label' => 'Vacaciones', 'actions' => ['view', 'edit']],
            ],
        ],
        [
            'key' => 'settings',
            'label' => 'Ajustes',
            'items' => [
                ['key' => 'cash_reduction', 'label' => 'Reducción de efectivo', 'actions' => ['view', 'edit']],
                ['key' => 'objectives', 'label' => 'Objetivos de venta', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'overtime', 'label' => 'Ajustes horas extras', 'actions' => ['view', 'edit']],
                ['key' => 'inventories', 'label' => 'Inventario (toggle anillos)', 'actions' => ['view']],
                ['key' => 'products', 'label' => 'Productos', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'daily_close', 'label' => 'Cierre de caja', 'actions' => ['view', 'edit']],
            ],
        ],
        [
            'key' => 'trash',
            'label' => 'Papelera',
            'items' => [
                ['key' => 'main', 'label' => 'Papelera', 'actions' => ['view', 'edit', 'delete']],
            ],
        ],
        [
            'key' => 'admin',
            'label' => 'Administración',
            'items' => [
                ['key' => 'company', 'label' => 'Empresa', 'actions' => ['view', 'edit']],
                ['key' => 'suppliers', 'label' => 'Proveedores', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'users', 'label' => 'Usuarios', 'actions' => ['view', 'create', 'edit', 'delete']],
                ['key' => 'roles', 'label' => 'Roles', 'actions' => ['view', 'edit']],
            ],
        ],
    ],
];
