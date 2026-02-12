<?php

namespace App\Console\Commands;

use App\Models\CashWalletExpense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupCashWalletExpenses extends Command
{
    protected $signature = 'cleanup:cash-wallet-expenses {--force : Ejecutar sin confirmación}';
    protected $description = 'Elimina registros huérfanos en cash_wallet_expenses cuyo financial_entry_id no existe en financial_entries';

    public function handle()
    {
        // Verificar que la tabla existe
        if (!Schema::hasTable('cash_wallet_expenses')) {
            $this->error('La tabla cash_wallet_expenses no existe.');
            return Command::FAILURE;
        }

        $this->info('Buscando registros huérfanos en cash_wallet_expenses...');

        // Buscar registros cuyo financial_entry_id no existe en financial_entries
        // Usamos una consulta SQL directa para mayor eficiencia
        $orphanedRecords = DB::table('cash_wallet_expenses')
            ->leftJoin('financial_entries', 'cash_wallet_expenses.financial_entry_id', '=', 'financial_entries.id')
            ->whereNull('financial_entries.id')
            ->select('cash_wallet_expenses.id', 'cash_wallet_expenses.financial_entry_id', 'cash_wallet_expenses.cash_wallet_id')
            ->get();

        $count = $orphanedRecords->count();

        if ($count === 0) {
            $this->info('✅ No se encontraron registros huérfanos.');
            return Command::SUCCESS;
        }

        $this->warn("Se encontraron {$count} registros huérfanos.");

        // Verificar si se debe ejecutar sin confirmación
        $force = $this->option('force');
        
        // Mostrar algunos detalles antes de eliminar
        if ($force || $this->confirm('¿Deseas eliminar estos registros?', true)) {
            // Obtener los IDs de los registros huérfanos
            $orphanedIds = $orphanedRecords->pluck('id')->toArray();
            
            // Eliminar en lote
            $deleted = CashWalletExpense::whereIn('id', $orphanedIds)->delete();

            $this->info("\n✅ Se eliminaron {$deleted} registros huérfanos de cash_wallet_expenses.");
            return Command::SUCCESS;
        } else {
            $this->info('Operación cancelada.');
            return Command::SUCCESS;
        }
    }
}
