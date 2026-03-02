<?php

namespace App\Console\Commands;

use App\Models\FinancialEntry;
use Illuminate\Console\Command;

/**
 * Elimina ingresos duplicados de cierre diario y deja solo un ingreso de efectivo
 * y uno de datáfono por día, recalculados desde el cierre diario (fuente de verdad).
 * Ejecutar en producción: php artisan financial:deduplicate-daily-close-incomes --from=2026-02-01 --to=2026-02-28
 */
class DeduplicateDailyCloseIncomes extends Command
{
    protected $signature = 'financial:deduplicate-daily-close-incomes
                            {--from= : Fecha desde (Y-m-d)}
                            {--to= : Fecha hasta (Y-m-d)}';

    protected $description = 'Elimina ingresos duplicados de cierre diario y regenera solo los correctos desde cada cierre';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        // Sin scope de empresa para procesar todos los cierres (consola no tiene sesión)
        $query = FinancialEntry::withoutGlobalScopes()
            ->with('store')
            ->where('type', 'daily_close')
            ->orderBy('date')
            ->orderBy('store_id');

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        $dailyCloses = $query->get();
        if ($dailyCloses->isEmpty()) {
            $this->warn('No se encontraron cierres diarios en el rango indicado.');
            return 0;
        }

        $this->info('Cierres diarios a procesar: ' . $dailyCloses->count());
        if (!$this->confirm('¿Eliminar ingresos duplicados y regenerar desde cada cierre?', true)) {
            return 0;
        }

        $deleted = 0;
        $created = 0;

        foreach ($dailyCloses as $close) {
            $storeId = $close->store_id;
            $date = $close->date;
            $storeName = $close->store->name ?? 'Tienda #' . $storeId;
            $dateStr = $date->format('d/m/Y');

            // Todos los ingresos de cierre_diario para esta tienda y fecha (cualquier formato de notes)
            $incomes = FinancialEntry::withoutGlobalScopes()
                ->where('type', 'income')
                ->where('store_id', $storeId)
                ->whereDate('date', $date)
                ->where(function ($q) use ($close) {
                    $q->where('income_category', 'cierre_diario')
                        ->orWhere('notes', 'LIKE', '%daily_close_id:' . $close->id . '%')
                        ->orWhere('notes', 'LIKE', '%Generado automáticamente desde cierre diario #' . $close->id . '%');
                })
                ->get();

            $toDelete = $incomes->count();
            if ($toDelete > 0) {
                foreach ($incomes as $income) {
                    $income->forceDelete();
                    $deleted++;
                }
            }

            // Recalcular desde el cierre (igual que en FinancialController::syncDailyCloseIncomes)
            $cashCounted = $close->calculateCashTotal();
            $cashInitial = (float) ($close->cash_initial ?? 0);
            $cashExpenses = (float) ($close->cash_expenses ?? 0);
            $cashSales = round($cashCounted - $cashInitial + $cashExpenses, 2);
            $tpv = (float) ($close->tpv ?? 0);

            $userId = $close->created_by ?? 1;
            $companyId = $close->company_id ?? null;
            $notes = 'daily_close_id:' . $close->id;
            $base = [
                'store_id' => $storeId,
                'date' => $date,
                'income_category' => 'cierre_diario',
                'notes' => $notes,
                'created_by' => $userId,
            ];
            if ($companyId !== null) {
                $base['company_id'] = $companyId;
            }

            if ($cashSales > 0) {
                FinancialEntry::create(array_merge($base, [
                    'type' => 'income',
                    'amount' => $cashSales,
                    'income_amount' => $cashSales,
                    'income_concept' => 'Ingreso efectivo cierre diario',
                    'expense_payment_method' => 'cash',
                ]));
                $created++;
            }
            if ($tpv > 0) {
                FinancialEntry::create(array_merge($base, [
                    'type' => 'income',
                    'amount' => $tpv,
                    'income_amount' => $tpv,
                    'income_concept' => 'Ingreso datáfono cierre diario',
                    'expense_payment_method' => 'bank',
                ]));
                $created++;
            }

            if ($toDelete > 0 || $cashSales > 0 || $tpv > 0) {
                $this->line("  {$dateStr} – {$storeName}: eliminados {$toDelete}, creados " . (($cashSales > 0 ? 1 : 0) + ($tpv > 0 ? 1 : 0)));
            }
        }

        $this->info("Listo. Ingresos eliminados: {$deleted}. Ingresos creados: {$created}.");
        return 0;
    }
}
