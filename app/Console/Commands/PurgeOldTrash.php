<?php

namespace App\Console\Commands;

use App\Models\FinancialEntry;
use Illuminate\Console\Command;

class PurgeOldTrash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trash:purge {--days=30 : Días desde la eliminación para purgar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina permanentemente los registros de la papelera con más de 30 días (configurable)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = FinancialEntry::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->forceDelete();

        $this->info("Se han eliminado permanentemente {$count} registro(s) con más de {$days} días en la papelera.");

        return Command::SUCCESS;
    }
}
