<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearProjectData extends Command
{
    protected $signature = 'project:clear-data
                            {--force : Ejecutar sin confirmación}';

    protected $description = 'Elimina todos los datos del proyecto excepto usuarios, roles y empleados. Deja el proyecto limpio para rellenar desde cero.';

    /**
     * Tablas que se vaciarán (datos de negocio). No se tocan: users, roles, employees,
     * employee_store, companies, stores, company_businesses, company_role_permissions.
     */
    private array $tablesToClear = [
        'dashboard_widgets',
        'employee_vacation_days',
        'employee_vacation_periods',
        'inventory_line_purchase_records',
        'inventory_lines',
        'inventories',
        'weekly_sales',
        'sales_weeks',
        'sales_months',
        'product_purchase_records',
        'product_ingredients',
        'product_inventories',
        'products',
        'product_categories',
        'inventory_purchase_records',
        'inventory_base_products',
        'inventory_bases',
        'ring_inventories',
        'overtime_records',
        'overtime_settings',
        'objective_daily_rows',
        'monthly_objective_settings',
        'order_payments',
        'expense_payments',
        'bank_movements',
        'transfers',
        'cash_wallet_expenses',
        'cash_withdrawals',
        'cash_wallet_transfers',
        'financial_entries',
        'invoices',
        'declared_sales',
        'store_cash_reductions',
        'cash_wallets',
        'bank_accounts',
        'payrolls',
        'suppliers',
        'orders',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'job_batches',
        'jobs',
        'failed_jobs',
    ];

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('¿Vaciar todos los datos excepto usuarios, roles y empleados? Esta acción no se puede deshacer.')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        $cleared = 0;
        $skipped = 0;

        foreach ($this->tablesToClear as $table) {
            if (!Schema::hasTable($table)) {
                $skipped++;
                continue;
            }
            try {
                DB::table($table)->truncate();
                $this->line("  <info>✓</info> {$table}");
                $cleared++;
            } catch (\Throwable $e) {
                $this->warn("  <comment>⚠</comment> {$table}: {$e->getMessage()}");
            }
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->newLine();
        $this->info("Listo. Se vaciaron {$cleared} tablas." . ($skipped > 0 ? " Se omitieron {$skipped} (no existen)." : ''));
        $this->info('Se mantienen: usuarios, roles, empleados, employee_store, companies, stores, company_businesses, company_role_permissions.');

        return 0;
    }
}
