<?php

namespace App\Console\Commands;

use App\Models\FinancialEntry;
use Illuminate\Console\Command;

class BackfillCashControlExpenseReportingMonths extends Command
{
    protected $signature = 'financial:backfill-cash-control-reporting-months
                            {--dry-run : Mostrar cambios sin guardar}
                            {--force : Ejecutar sin pedir confirmación}';

    protected $description = 'Asigna reporting_month desde notes.procedence_date en gastos históricos de control de efectivo.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $baseQuery = FinancialEntry::withoutCompanyScope()
            ->where('type', 'expense')
            ->where('expense_source', 'control_efectivo');

        $total = (clone $baseQuery)->count();
        $this->info("Gastos con origen control_efectivo: {$total}");

        $toChange = 0;
        $skipped = ['sin_notas' => 0, 'json_invalido' => 0, 'sin_procedencia' => 0, 'formato_procedencia' => 0];
        $previewLines = 0;
        $previewLimit = 40;

        $baseQuery->orderBy('id')->chunkById(200, function ($entries) use (&$toChange, &$skipped, &$previewLines, $previewLimit, $dryRun) {
            foreach ($entries as $entry) {
                $newMonth = $this->reportingMonthFromNotes($entry, $skipped);
                if ($newMonth === null) {
                    continue;
                }
                if ($entry->reporting_month === $newMonth) {
                    continue;
                }
                $toChange++;
                if ($dryRun && $previewLines < $previewLimit) {
                    $old = $entry->reporting_month ?? '(null)';
                    $dateStr = $entry->date?->format('Y-m-d') ?? '';
                    $this->line("ID {$entry->id}: reporting_month {$old} → {$newMonth} (fecha gasto: {$dateStr})");
                    $previewLines++;
                }
            }
        });

        if ($toChange === 0) {
            $this->info('Ningún registro necesita cambio (o no hay datos válidos en notes.procedence_date).');
            $this->printSkipped($skipped);

            return Command::SUCCESS;
        }

        if ($dryRun) {
            if ($toChange > $previewLimit) {
                $this->warn('… y '.($toChange - $previewLimit).' cambios más (usa sin --dry-run para aplicar).');
            }
            $this->info("Total a actualizar: {$toChange}");
            $this->printSkipped($skipped);
            $this->comment('Quita --dry-run para escribir en base de datos.');

            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm("Se actualizarán {$toChange} registros. ¿Continuar?", true)) {
            $this->info('Operación cancelada.');

            return Command::SUCCESS;
        }

        $updated = 0;
        $baseQuery->orderBy('id')->chunkById(200, function ($entries) use (&$updated) {
            $dummySkipped = null;
            foreach ($entries as $entry) {
                $newMonth = $this->reportingMonthFromNotes($entry, $dummySkipped);
                if ($newMonth === null || $entry->reporting_month === $newMonth) {
                    continue;
                }
                $entry->reporting_month = $newMonth;
                $entry->saveQuietly();
                $updated++;
            }
        });

        $this->info("Actualizados: {$updated}");
        $this->printSkipped($skipped);

        return Command::SUCCESS;
    }

    /**
     * @param  array{sin_notas: int, json_invalido: int, sin_procedencia: int, formato_procedencia: int}|null  $skipped
     */
    private function reportingMonthFromNotes(FinancialEntry $entry, ?array &$skipped): ?string
    {
        $raw = $entry->notes;
        if ($raw === null || $raw === '') {
            if ($skipped !== null) {
                $skipped['sin_notas']++;
            }

            return null;
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            if ($skipped !== null) {
                $skipped['json_invalido']++;
            }

            return null;
        }

        $proc = $data['procedence_date'] ?? null;
        if (! is_string($proc) || $proc === '') {
            if ($skipped !== null) {
                $skipped['sin_procedencia']++;
            }

            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $proc)) {
            return $proc;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $proc)) {
            return substr($proc, 0, 7);
        }

        if ($skipped !== null) {
            $skipped['formato_procedencia']++;
        }

        return null;
    }

    /**
     * @param  array<string, int>  $skipped
     */
    private function printSkipped(array $skipped): void
    {
        $sum = array_sum($skipped);
        if ($sum === 0) {
            return;
        }
        $this->newLine();
        $this->comment('Omitidos al derivar mes desde notes:');
        foreach ($skipped as $reason => $count) {
            if ($count > 0) {
                $this->line("  • {$reason}: {$count}");
            }
        }
    }
}
