<?php

namespace App\Console\Commands;

use App\Models\FinancialEntry;
use Illuminate\Console\Command;

/**
 * Limpia ingresos/gastos generados desde cierres diarios que ya no existen.
 * Útil si se borró un cierre desde papelera (forceDelete) sin arrastrar los registros asociados.
 */
class CleanupOrphanDailyCloseEntries extends Command
{
    protected $signature = 'financial:cleanup-orphan-daily-close-entries
                            {--dry-run : Mostrar cambios sin guardar}
                            {--force : Ejecutar sin pedir confirmación}';

    protected $description = 'Elimina ingresos/gastos de cierre diario cuyo daily_close_id ya no existe.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        // Consola no tiene sesión; evitar scope de empresa.
        $dailyCloseIds = FinancialEntry::withoutGlobalScopes()
            ->withTrashed()
            ->where('type', 'daily_close')
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->flip(); // set

        $orphansIncome = $this->findOrphanDailyCloseIncomes($dailyCloseIds);
        $orphansExpense = $this->findOrphanDailyCloseExpenses($dailyCloseIds);

        $total = $orphansIncome->count() + $orphansExpense->count();
        $this->info("Huérfanos detectados: {$total} (ingresos: {$orphansIncome->count()}, gastos: {$orphansExpense->count()})");

        if ($total === 0) {
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry-run: no se han aplicado cambios.');
            $this->preview('Ingresos', $orphansIncome);
            $this->preview('Gastos', $orphansExpense);

            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm("Se eliminarán (soft delete) {$total} registros huérfanos. ¿Continuar?", true)) {
            $this->info('Operación cancelada.');

            return Command::SUCCESS;
        }

        $deletedIncome = 0;
        foreach ($orphansIncome as $id) {
            FinancialEntry::withoutGlobalScopes()->where('id', $id)->delete();
            $deletedIncome++;
        }

        $deletedExpense = 0;
        foreach ($orphansExpense as $id) {
            FinancialEntry::withoutGlobalScopes()->where('id', $id)->delete();
            $deletedExpense++;
        }

        $this->info("Eliminados (soft): ingresos {$deletedIncome}, gastos {$deletedExpense}.");

        return Command::SUCCESS;
    }

    private function findOrphanDailyCloseIncomes($dailyCloseIdSet)
    {
        // Ingresos con notes daily_close_id:<id> o "Generado automáticamente..." pero cuyo cierre ya no existe.
        // Extraemos ids con regex en PHP para no depender de funciones SQL específicas.
        $ids = [];

        FinancialEntry::withoutGlobalScopes()
            ->where('type', 'income')
            ->where(function ($q) {
                $q->where('notes', 'LIKE', '%daily_close_id:%')
                    ->orWhere('notes', 'LIKE', '%Generado automáticamente desde cierre diario #%');
            })
            ->select(['id', 'notes'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$ids, $dailyCloseIdSet) {
                foreach ($rows as $row) {
                    $notes = (string) ($row->notes ?? '');
                    $closeId = $this->extractDailyCloseIdFromIncomeNotes($notes);
                    if ($closeId === null) {
                        continue;
                    }
                    if (! isset($dailyCloseIdSet[$closeId])) {
                        $ids[] = (int) $row->id;
                    }
                }
            });

        return collect($ids);
    }

    private function findOrphanDailyCloseExpenses($dailyCloseIdSet)
    {
        $ids = [];

        FinancialEntry::withoutGlobalScopes()
            ->where('type', 'expense')
            ->where('expense_source', 'cierre_diario')
            ->where(function ($q) {
                $q->where('notes', 'LIKE', '%"daily_close_id":%');
            })
            ->select(['id', 'notes'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$ids, $dailyCloseIdSet) {
                foreach ($rows as $row) {
                    $notes = (string) ($row->notes ?? '');
                    $closeId = $this->extractDailyCloseIdFromExpenseNotes($notes);
                    if ($closeId === null) {
                        continue;
                    }
                    if (! isset($dailyCloseIdSet[$closeId])) {
                        $ids[] = (int) $row->id;
                    }
                }
            });

        return collect($ids);
    }

    private function extractDailyCloseIdFromIncomeNotes(string $notes): ?int
    {
        if (preg_match('/daily_close_id:(\d+)/', $notes, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/cierre diario #(\d+)/i', $notes, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function extractDailyCloseIdFromExpenseNotes(string $notes): ?int
    {
        $data = json_decode($notes, true);
        if (is_array($data) && isset($data['daily_close_id']) && is_numeric($data['daily_close_id'])) {
            return (int) $data['daily_close_id'];
        }
        if (preg_match('/"daily_close_id":\s*(\d+)/', $notes, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function preview(string $label, $ids): void
    {
        $count = $ids->count();
        if ($count === 0) {
            return;
        }

        $this->newLine();
        $this->comment($label.' (primeros 30 IDs):');
        $slice = $ids->take(30)->all();
        $this->line(implode(', ', $slice));
        if ($count > 30) {
            $this->line('… y '.($count - 30).' más');
        }
    }
}
