<?php

namespace App\Support;

use App\Models\User;

/**
 * Registro central de widgets del dashboard.
 * Lee de config/dashboard_widgets.php
 *
 * REGLA CRÍTICA: Si el usuario tiene dashboard.main.view, tiene acceso a los widgets
 * del dashboard (ingresos, gastos, gráficas, registros). El dashboard nunca debe mostrarse vacío.
 */
class WidgetRegistry
{
    /**
     * Todas las definiciones de widgets.
     */
    public static function all(): array
    {
        return config('dashboard_widgets', []);
    }

    public static function getLabel(string $key): string
    {
        return self::all()[$key]['name'] ?? $key;
    }

    public static function getPermission(string $key): ?string
    {
        return self::all()[$key]['permission'] ?? null;
    }

    public static function getDefaultSize(string $key): array
    {
        $def = self::all()[$key] ?? null;
        if (!$def) {
            return ['w' => 4, 'h' => 2];
        }
        return [
            'w' => $def['default_width'] ?? 4,
            'h' => $def['default_height'] ?? 2,
        ];
    }

    public static function getView(string $key): string
    {
        return self::all()[$key]['component'] ?? 'dashboard.widgets.placeholder';
    }

    public static function isResizable(string $key): bool
    {
        return self::all()[$key]['resizable'] ?? true;
    }

    public static function isMovable(string $key): bool
    {
        return self::all()[$key]['movable'] ?? true;
    }

    /**
     * Widgets disponibles para el usuario.
     *
     * REGLA: Si tiene dashboard.main.view, tiene acceso a los widgets del dashboard
     * (ultimos_registros, gastos_por_categoria, ingresos_por_metodo, evolucion_ventas).
     * Así el dashboard nunca se muestra vacío.
     */
    public static function getAvailableKeys(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return [];
        }

        $available = [];
        $hasDashboardAccess = $user->hasPermission('dashboard.main.view');

        foreach (self::all() as $key => $def) {
            $permission = $def['permission'] ?? null;
            $permissionAny = $def['permission_any'] ?? null;

            if ($permissionAny) {
                // accesos_rapidos: visible si tiene al menos uno
                foreach ($permissionAny as $p) {
                    if ($user->hasPermission($p)) {
                        $available[] = $key;
                        break;
                    }
                }
            } elseif ($permission) {
                // Si tiene el permiso específico, lo ve
                if ($user->hasPermission($permission)) {
                    $available[] = $key;
                }
                // FALLBACK: Si tiene dashboard.main.view, incluye widgets del dashboard
                elseif ($hasDashboardAccess && self::isDashboardWidget($key)) {
                    $available[] = $key;
                }
            }
        }

        $available = array_values(array_unique($available));

        // Último recurso: si está vacío pero el usuario tiene acceso al dashboard, darle los widgets básicos
        if (empty($available) && $hasDashboardAccess) {
            return ['ultimos_registros', 'gastos_por_categoria', 'ingresos_por_metodo', 'evolucion_ventas'];
        }

        return $available;
    }

    /**
     * Widgets que pertenecen al módulo dashboard (no orders, hr, etc.)
     */
    private static function isDashboardWidget(string $key): bool
    {
        return in_array($key, [
            'ultimos_registros',
            'gastos_por_categoria',
            'ingresos_por_metodo',
            'evolucion_ventas',
        ], true);
    }

    /**
     * Layout por defecto para usuarios sin layout guardado.
     * NUNCA devuelve vacío si el usuario tiene dashboard.main.view.
     */
    public static function defaultLayout(?User $user = null): array
    {
        $available = self::getAvailableKeys($user);

        if (empty($available)) {
            return [];
        }

        $template = [
            ['key' => 'accesos_rapidos', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 1],
            ['key' => 'evolucion_ventas', 'x' => 0, 'y' => 1, 'w' => 12, 'h' => 2],
            ['key' => 'ingresos_por_metodo', 'x' => 0, 'y' => 3, 'w' => 6, 'h' => 2],
            ['key' => 'gastos_por_categoria', 'x' => 6, 'y' => 3, 'w' => 6, 'h' => 2],
            ['key' => 'pedidos_pagado_vs_pendiente', 'x' => 0, 'y' => 5, 'w' => 4, 'h' => 2],
            ['key' => 'horas_extras_rrhh', 'x' => 4, 'y' => 5, 'w' => 4, 'h' => 2],
            ['key' => 'ultimos_registros', 'x' => 8, 'y' => 5, 'w' => 4, 'h' => 3],
        ];

        $layout = [];
        foreach ($template as $item) {
            if (in_array($item['key'], $available, true)) {
                $layout[] = array_merge($item, ['minimized' => false]);
            }
        }

        return $layout;
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::all() as $key => $def) {
            $labels[$key] = $def['name'] ?? $key;
        }
        return $labels;
    }

    public static function has(string $key): bool
    {
        return isset(self::all()[$key]);
    }
}
