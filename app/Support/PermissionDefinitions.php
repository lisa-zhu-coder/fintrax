<?php

namespace App\Support;

class PermissionDefinitions
{
    /**
     * Devuelve todas las claves de permiso en formato modulo.submodulo.accion
     *
     * @return array<string>
     */
    public static function allKeys(): array
    {
        $keys = [];
        $config = config('permissions', []);
        $actionLabels = $config['action_labels'] ?? [];
        foreach ($config['modules'] ?? [] as $module) {
            $moduleKey = $module['key'];
            foreach ($module['items'] ?? [] as $item) {
                $itemKey = $item['key'];
                foreach ($item['actions'] ?? [] as $action) {
                    $keys[] = "{$moduleKey}.{$itemKey}.{$action}";
                }
            }
        }
        return $keys;
    }

    /**
     * Estructura para la UI: módulos con ítems y cada ítem con permisos (key + label acción)
     *
     * @return array<int, array{key: string, label: string, items: array<int, array{key: string, label: string, permissions: array<int, array{key: string, action: string, label: string}>}>}>
     */
    public static function forUi(): array
    {
        $config = config('permissions', []);
        $actionLabels = $config['action_labels'] ?? [];
        $result = [];
        foreach ($config['modules'] ?? [] as $module) {
            $moduleKey = $module['key'];
            $itemsWithPermissions = [];
            foreach ($module['items'] ?? [] as $item) {
                $itemKey = $item['key'];
                $permissions = [];
                foreach ($item['actions'] ?? [] as $action) {
                    $key = "{$moduleKey}.{$itemKey}.{$action}";
                    $permissions[] = [
                        'key' => $key,
                        'action' => $action,
                        'label' => $actionLabels[$action] ?? $action,
                    ];
                }
                $itemsWithPermissions[] = [
                    'key' => $itemKey,
                    'label' => $item['label'],
                    'permissions' => $permissions,
                ];
            }
            $result[] = [
                'key' => $moduleKey,
                'label' => $module['label'],
                'items' => $itemsWithPermissions,
            ];
        }
        return $result;
    }

    /**
     * Todas las claves de un módulo (por key de módulo)
     *
     * @return array<string, array<string>>
     */
    public static function keysByModule(): array
    {
        $byModule = [];
        $config = config('permissions', []);
        foreach ($config['modules'] ?? [] as $module) {
            $moduleKey = $module['key'];
            $byModule[$moduleKey] = [];
            foreach ($module['items'] ?? [] as $item) {
                $itemKey = $item['key'];
                foreach ($item['actions'] ?? [] as $action) {
                    $byModule[$moduleKey][] = "{$moduleKey}.{$itemKey}.{$action}";
                }
            }
        }
        return $byModule;
    }
}
