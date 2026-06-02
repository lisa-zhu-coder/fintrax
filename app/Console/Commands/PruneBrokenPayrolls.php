<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneBrokenPayrolls extends Command
{
    protected $signature = 'payrolls:prune-broken
                            {--dry-run : No borra, solo muestra qué eliminaría}
                            {--force : Borra sin pedir confirmación}';

    protected $description = 'Elimina nóminas irrecuperables (sin PDF en disco y sin base64).';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $brokenIds = [];
        Payroll::withTrashed()->orderBy('id')->chunk(200, function ($payrolls) use ($disk, &$brokenIds) {
            foreach ($payrolls as $payroll) {
                $hasFile = $payroll->file_path && $disk->exists($payroll->file_path);
                $hasBase64 = ! empty($payroll->base64_content);

                if (! $hasFile && ! $hasBase64) {
                    $brokenIds[] = (int) $payroll->id;
                }
            }
        });

        if (empty($brokenIds)) {
            $this->info('No hay nóminas irrecuperables.');

            return self::SUCCESS;
        }

        $this->warn('Nóminas irrecuperables encontradas: ' . count($brokenIds));
        $this->line('IDs: ' . implode(', ', $brokenIds));

        if ($dryRun) {
            $this->info('Dry run: no se ha eliminado nada.');

            return self::SUCCESS;
        }

        if (! $force) {
            if (! $this->confirm('¿Seguro que quieres eliminarlas? Esta acción no se puede deshacer.')) {
                $this->info('Cancelado.');

                return self::SUCCESS;
            }
        }

        $deleted = 0;
        Payroll::withTrashed()->whereIn('id', $brokenIds)->chunk(200, function ($payrolls) use ($disk, &$deleted) {
            foreach ($payrolls as $payroll) {
                if ($payroll->file_path && $disk->exists($payroll->file_path)) {
                    $disk->delete($payroll->file_path);
                }
                $payroll->forceDelete();
                $deleted++;
            }
        });

        $this->info("Eliminadas: {$deleted}");

        return self::SUCCESS;
    }
}

