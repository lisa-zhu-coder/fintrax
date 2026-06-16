<?php

namespace App\Support;

use App\Models\Company;

class OrderTableSettings
{
    /**
     * @return array<string, array{label: string, columns: array<string, array{label: string, sortable?: bool, sort_key?: string, align?: string, locked?: bool}>}>
     */
    public static function tableDefinitions(): array
    {
        return [
            'suppliers_list' => [
                'label' => 'Listado de proveedores',
                'columns' => [
                    'supplier' => ['label' => 'Proveedor', 'sortable' => true, 'sort_key' => 'supplier'],
                    'type' => ['label' => 'Tipo', 'sortable' => true, 'sort_key' => 'type'],
                    'total_orders' => ['label' => 'Pedidos', 'sortable' => true, 'sort_key' => 'total_orders', 'align' => 'right'],
                    'total_amount' => ['label' => 'Importe total', 'sortable' => true, 'sort_key' => 'total_amount', 'align' => 'right'],
                    'total_paid' => ['label' => 'Importe pagado', 'sortable' => true, 'sort_key' => 'total_paid', 'align' => 'right'],
                    'total_pending' => ['label' => 'Importe pendiente', 'sortable' => true, 'sort_key' => 'total_pending', 'align' => 'right'],
                    'actions' => ['label' => 'Acciones', 'locked' => true],
                ],
            ],
            'supplier_store_summary' => [
                'label' => 'Resumen por tienda (proveedor)',
                'columns' => [
                    'store_name' => ['label' => 'Tienda', 'sortable' => true, 'sort_key' => 'store_name'],
                    'total_orders' => ['label' => 'Pedidos', 'sortable' => true, 'sort_key' => 'store_total_orders', 'align' => 'right'],
                    'total_amount' => ['label' => 'Total', 'sortable' => true, 'sort_key' => 'store_total_amount', 'align' => 'right'],
                    'total_paid' => ['label' => 'Pagado', 'sortable' => true, 'sort_key' => 'store_total_paid', 'align' => 'right'],
                    'total_pending' => ['label' => 'Pendiente', 'sortable' => true, 'sort_key' => 'store_total_pending', 'align' => 'right'],
                ],
            ],
            'supplier_orders' => [
                'label' => 'Pedidos del proveedor',
                'columns' => [
                    'status' => ['label' => 'Estado', 'sortable' => true, 'sort_key' => 'status'],
                    'date' => ['label' => 'Fecha', 'sortable' => true, 'sort_key' => 'date'],
                    'store' => ['label' => 'Tienda', 'sortable' => true, 'sort_key' => 'store'],
                    'split_type' => ['label' => 'Tipo'],
                    'origin_store' => ['label' => 'Tienda origen'],
                    'invoice_number' => ['label' => 'Nº Factura', 'sortable' => true, 'sort_key' => 'invoice_number'],
                    'order_number' => ['label' => 'Nº Pedido', 'sortable' => true, 'sort_key' => 'order_number'],
                    'concept' => ['label' => 'Concepto', 'sortable' => true, 'sort_key' => 'concept'],
                    'amount' => ['label' => 'Importe', 'sortable' => true, 'sort_key' => 'amount', 'align' => 'right'],
                    'total_paid' => ['label' => 'Pagado', 'sortable' => true, 'sort_key' => 'total_paid', 'align' => 'right'],
                    'pending' => ['label' => 'Pendiente', 'sortable' => true, 'sort_key' => 'pending', 'align' => 'right'],
                    'payment_methods' => ['label' => 'Formas de pago'],
                    'actions' => ['label' => 'Acciones', 'align' => 'center', 'locked' => true],
                ],
            ],
        ];
    }

    /** @return list<string> */
    public static function tableKeys(): array
    {
        return array_keys(self::tableDefinitions());
    }

    public static function company(): ?Company
    {
        $companyId = session('company_id');

        return $companyId
            ? Company::withoutGlobalScopes()->find($companyId)
            : null;
    }

    /** @return array<string, list<array{key: string, visible: bool}>> */
    public static function defaultConfig(): array
    {
        $config = [];

        foreach (self::tableDefinitions() as $tableKey => $table) {
            $config[$tableKey] = [];
            foreach (array_keys($table['columns']) as $columnKey) {
                $config[$tableKey][] = [
                    'key' => $columnKey,
                    'visible' => true,
                ];
            }
        }

        return $config;
    }

    /** @return array<string, list<array{key: string, visible: bool}>> */
    public static function resolveConfig(?Company $company = null): array
    {
        $company = $company ?? self::company();
        $defaults = self::defaultConfig();
        $saved = $company?->orders_table_settings;

        if (! is_array($saved)) {
            return $defaults;
        }

        $resolved = [];

        foreach (self::tableDefinitions() as $tableKey => $table) {
            $validKeys = array_keys($table['columns']);
            $savedTable = $saved[$tableKey] ?? [];
            $resolved[$tableKey] = [];

            if (is_array($savedTable)) {
                foreach ($savedTable as $item) {
                    $key = $item['key'] ?? null;
                    if (! is_string($key) || ! in_array($key, $validKeys, true)) {
                        continue;
                    }
                    $locked = (bool) ($table['columns'][$key]['locked'] ?? false);
                    $resolved[$tableKey][] = [
                        'key' => $key,
                        'visible' => $locked ? true : (bool) ($item['visible'] ?? true),
                    ];
                }
            }

            foreach ($validKeys as $key) {
                if (! collect($resolved[$tableKey])->contains(fn (array $col) => $col['key'] === $key)) {
                    $resolved[$tableKey][] = [
                        'key' => $key,
                        'visible' => true,
                    ];
                }
            }
        }

        return $resolved;
    }

    /**
     * @return list<array{key: string, label: string, sortable: bool, sort_key: ?string, align: string, locked: bool}>
     */
    public static function visibleColumns(string $tableKey, ?Company $company = null): array
    {
        $definitions = self::tableDefinitions()[$tableKey]['columns'] ?? [];
        $config = self::resolveConfig($company);
        $columns = [];

        foreach ($config[$tableKey] ?? [] as $item) {
            if (! ($item['visible'] ?? false)) {
                continue;
            }

            $key = $item['key'];
            $meta = $definitions[$key] ?? null;
            if ($meta === null) {
                continue;
            }

            $columns[] = [
                'key' => $key,
                'label' => $meta['label'],
                'sortable' => (bool) ($meta['sortable'] ?? false),
                'sort_key' => $meta['sort_key'] ?? null,
                'align' => $meta['align'] ?? 'left',
                'locked' => (bool) ($meta['locked'] ?? false),
            ];
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<array{key: string, visible: bool}>>
     */
    public static function normalizeInput(array $input): array
    {
        $normalized = [];

        foreach (self::tableDefinitions() as $tableKey => $table) {
            $validKeys = array_keys($table['columns']);
            $rows = $input[$tableKey] ?? [];
            $normalized[$tableKey] = [];

            if (! is_array($rows)) {
                $normalized[$tableKey] = self::defaultConfig()[$tableKey];

                continue;
            }

            $seen = [];
            foreach ($rows as $row) {
                $key = $row['key'] ?? null;
                if (! is_string($key) || ! in_array($key, $validKeys, true) || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $locked = (bool) ($table['columns'][$key]['locked'] ?? false);
                $normalized[$tableKey][] = [
                    'key' => $key,
                    'visible' => $locked ? true : filter_var($row['visible'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ];
            }

            foreach ($validKeys as $key) {
                if (! isset($seen[$key])) {
                    $normalized[$tableKey][] = [
                        'key' => $key,
                        'visible' => true,
                    ];
                }
            }
        }

        return $normalized;
    }
}
