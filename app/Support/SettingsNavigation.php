<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Collection;

class SettingsNavigation
{
    public static function company(): ?Company
    {
        $companyId = session('company_id');

        return $companyId
            ? Company::withoutGlobalScopes()->find($companyId)
            : null;
    }

    /**
     * @return array<string, array{label: string, module: ?string, hub_only?: bool}>
     */
    public static function groupMeta(): array
    {
        return [
            'dashboard' => ['label' => 'Ajuste dashboard', 'module' => null, 'hub_only' => true],
            'finance' => ['label' => 'Ajuste finanzas', 'module' => null],
            'invoices' => ['label' => 'Ajuste facturas', 'module' => null, 'hub_only' => true],
            'orders' => ['label' => 'Ajuste pedidos', 'module' => null],
            'inventory' => ['label' => 'Ajuste inventarios', 'module' => null],
            'hr' => ['label' => 'Ajuste RR.HH.', 'module' => null],
            'clients' => ['label' => 'Ajuste clientes', 'module' => 'clients', 'hub_only' => true],
        ];
    }

    /**
     * @return array<string, list<array{route: string, routes: list<string>, label: string, permission: ?string}>>
     */
    public static function tabDefinitions(): array
    {
        return [
            'finance' => [
                [
                    'route' => 'store-cash-reductions.index',
                    'routes' => ['store-cash-reductions.*'],
                    'label' => 'Reducción de efectivo por tienda',
                    'permission' => 'settings.cash_reduction.view',
                ],
                [
                    'route' => 'objectives-settings.index',
                    'routes' => ['objectives-settings.*'],
                    'label' => 'Objetivos de ventas',
                    'permission' => 'settings.objectives.view',
                ],
                [
                    'route' => 'daily-close-settings.index',
                    'routes' => ['daily-close-settings.*'],
                    'label' => 'Cierre de caja',
                    'permission' => 'settings.daily_close.view',
                ],
                [
                    'route' => 'expense-categories-settings.index',
                    'routes' => ['expense-categories-settings.*'],
                    'label' => 'Categorías de gastos',
                    'permission' => 'settings.expense_categories.view',
                ],
                [
                    'route' => 'loan-types-settings.index',
                    'routes' => ['loan-types-settings.*'],
                    'label' => 'Tipos de préstamo',
                    'permission' => 'settings.loan_types.manage',
                ],
            ],
            'inventory' => [
                [
                    'route' => 'product-settings.index',
                    'routes' => ['product-settings.*'],
                    'label' => 'Productos',
                    'permission' => 'settings.products.view',
                ],
            ],
            'hr' => [
                [
                    'route' => 'overtime-settings.index',
                    'routes' => ['overtime-settings.*', 'overtime-types.*'],
                    'label' => 'Ajustes de horas extras',
                    'permission' => 'settings.overtime.view',
                ],
                [
                    'route' => 'job-positions-settings.index',
                    'routes' => ['job-positions-settings.*'],
                    'label' => 'Puestos de empleado',
                    'permission' => 'settings.job_positions.manage',
                ],
                [
                    'route' => 'email-templates-settings.index',
                    'routes' => ['email-templates-settings.*'],
                    'label' => 'Plantillas de email RRHH',
                    'permission' => 'settings.payroll_templates.manage',
                ],
            ],
            'orders' => [
                [
                    'route' => 'order-table-settings.index',
                    'routes' => ['order-table-settings.*'],
                    'label' => 'Columnas de tablas',
                    'permission' => 'orders.main.edit',
                ],
            ],
        ];
    }

    public static function isModuleEnabled(?string $module): bool
    {
        if ($module === null) {
            return true;
        }

        $company = self::company();
        if ($company === null) {
            return false;
        }

        return match ($module) {
            'clients' => (bool) $company->clients_module_enabled,
            'rings' => (bool) $company->rings_inventory_enabled,
            default => true,
        };
    }

    public static function userCanAccess(?string $permission): bool
    {
        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        if ($permission === null) {
            return $user->isSuperAdmin();
        }

        return $user->isSuperAdmin() || $user->hasPermission($permission);
    }

    /** @return Collection<int, array{href: string, label: string, active: bool}> */
    public static function tabsFor(string $group): Collection
    {
        $meta = self::groupMeta()[$group] ?? null;
        if ($meta === null || ! self::isModuleEnabled($meta['module'] ?? null)) {
            return collect();
        }

        return self::accessibleTabsFor($group);
    }

    /** @return Collection<int, array{href: string, label: string, active: bool}> */
    private static function accessibleTabsFor(string $group): Collection
    {
        $definitions = self::tabDefinitions()[$group] ?? [];

        return collect($definitions)
            ->filter(fn (array $tab) => self::userCanAccess($tab['permission']))
            ->map(function (array $tab) {
                $active = collect($tab['routes'])->contains(fn (string $pattern) => request()->routeIs($pattern));

                return [
                    'href' => route($tab['route']),
                    'label' => $tab['label'],
                    'active' => $active,
                ];
            })
            ->values();
    }

    public static function isGroupVisible(string $group): bool
    {
        $meta = self::groupMeta()[$group] ?? null;
        if ($meta === null) {
            return false;
        }

        if (! self::isModuleEnabled($meta['module'] ?? null)) {
            return false;
        }

        if (! empty($meta['hub_only'])) {
            return self::userHasAnySettingsAccess();
        }

        return self::accessibleTabsFor($group)->isNotEmpty();
    }

    public static function groupHref(string $group): ?string
    {
        if (! self::isGroupVisible($group)) {
            return null;
        }

        $tabs = self::accessibleTabsFor($group);
        if ($tabs->isNotEmpty()) {
            return $tabs->first()['href'];
        }

        return route('settings-hub.show', ['group' => $group]);
    }

    public static function isGroupActive(string $group): bool
    {
        if (request()->routeIs('settings-hub.show') && request()->route('group') === $group) {
            return true;
        }

        $definitions = self::tabDefinitions()[$group] ?? [];
        foreach ($definitions as $tab) {
            foreach ($tab['routes'] as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function currentGroup(): ?string
    {
        foreach (array_keys(self::groupMeta()) as $group) {
            if (self::isGroupActive($group)) {
                return $group;
            }
        }

        if (request()->routeIs('module-settings.*')) {
            return 'modules';
        }

        return null;
    }

    /** @return Collection<int, array{key: string, label: string, href: string}> */
    public static function sidebarGroups(): Collection
    {
        return collect(self::groupMeta())
            ->map(fn (array $meta, string $key) => [
                'key' => $key,
                'label' => $meta['label'],
                'href' => self::groupHref($key),
            ])
            ->filter(fn (array $item) => $item['href'] !== null)
            ->values();
    }

    public static function userHasAnySettingsAccess(): bool
    {
        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = [
            'settings.cash_reduction.view',
            'settings.objectives.view',
            'settings.overtime.view',
            'settings.products.view',
            'settings.daily_close.view',
            'settings.expense_categories.view',
            'settings.loan_types.manage',
            'settings.job_positions.manage',
            'settings.payroll_templates.manage',
        ];

        return $user->hasAnyPermission($permissions);
    }

    public static function showSettingsMenu(): bool
    {
        return self::userHasAnySettingsAccess() || auth()->user()?->isSuperAdmin();
    }
}
