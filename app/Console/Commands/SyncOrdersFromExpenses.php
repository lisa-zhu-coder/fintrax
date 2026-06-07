<?php

namespace App\Console\Commands;

use App\Services\ExpenseOrderSyncService;
use Illuminate\Console\Command;

class SyncOrdersFromExpenses extends Command
{
    protected $signature = 'orders:sync-from-expenses
                            {--company= : Solo gastos de esta empresa (company_id)}
                            {--dry-run : Simular sin crear pedidos}';

    protected $description = 'Crea o actualiza pedidos a partir de gastos con proveedor que aún no tienen pedido enlazado.';

    public function handle(ExpenseOrderSyncService $sync): int
    {
        $companyId = $this->option('company');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo dry-run: no se guardará nada.');
        }

        $result = $sync->syncAllExpensesWithSupplier(
            $companyId !== null ? (int) $companyId : null,
            $dryRun
        );

        $this->info(sprintf(
            'Procesados: %d | Nuevos pedidos: %d | Actualizados: %d | Omitidos (ya vienen de pedido): %d',
            $result['processed'],
            $result['created'],
            $result['updated'],
            $result['skipped']
        ));

        return self::SUCCESS;
    }
}
