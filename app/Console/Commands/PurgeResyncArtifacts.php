<?php

namespace App\Console\Commands;

use App\Models\CashWalletExpense;
use App\Models\FinancialEntry;
use Illuminate\Console\Command;

class PurgeResyncArtifacts extends Command
{
    protected $signature = 'trash:purge-resync-artifacts
                            {--force : Aplicar forceDelete (sin esto solo dry-run)}';

    protected $description = 'Purga de la papelera gastos source=order que son artefactos de recreate (existe gasto activo con el mismo order_id)';

    public function handle(): int
    {
        $apply = (bool) $this->option('force');

        $trashed = FinancialEntry::onlyTrashed()
            ->where('type', 'expense')
            ->where('notes', 'like', '%"source":"order"%')
            ->get();

        $toPurge = collect();

        foreach ($trashed as $entry) {
            $notes = $this->decodeNotes($entry->notes);
            $orderId = (int) ($notes['order_id'] ?? 0);
            if ($orderId <= 0 || ($notes['source'] ?? null) !== 'order') {
                continue;
            }

            $hasActive = FinancialEntry::query()
                ->where('type', 'expense')
                ->where('notes', 'like', '%"order_id":'.$orderId.'%')
                ->get()
                ->contains(function (FinancialEntry $active) use ($orderId) {
                    $n = $this->decodeNotes($active->notes);

                    return (int) ($n['order_id'] ?? 0) === $orderId
                        && ($n['source'] ?? null) === 'order';
                });

            if ($hasActive) {
                $toPurge->push($entry);
            }
        }

        $count = $toPurge->count();

        if ($count === 0) {
            $this->info('No se encontraron artefactos de recreate en la papelera.');

            return Command::SUCCESS;
        }

        if (! $apply) {
            $this->warn("Dry-run: se purgarían {$count} registro(s). Usa --force para aplicar.");
            foreach ($toPurge->take(20) as $entry) {
                $notes = $this->decodeNotes($entry->notes);
                $this->line("  #{$entry->id} order_id=".($notes['order_id'] ?? '?')." deleted_at={$entry->deleted_at}");
            }
            if ($count > 20) {
                $this->line('  ... y '.($count - 20).' más');
            }

            return Command::SUCCESS;
        }

        $purged = 0;
        foreach ($toPurge as $entry) {
            CashWalletExpense::where('financial_entry_id', $entry->id)->delete();
            $entry->forceDelete();
            $purged++;
        }

        $this->info("Se han eliminado permanentemente {$purged} artefacto(s) de recreate de la papelera.");

        return Command::SUCCESS;
    }

    /**
     * @param  mixed  $notes
     * @return array<string, mixed>
     */
    private function decodeNotes($notes): array
    {
        if ($notes === null || $notes === '') {
            return [];
        }
        if (is_array($notes)) {
            return $notes;
        }
        $decoded = @json_decode((string) $notes, true);

        return is_array($decoded) ? $decoded : [];
    }
}
