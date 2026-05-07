<?php

namespace App\Console\Commands;

use App\Models\BankMovement;
use App\Models\ExpensePayment;
use App\Models\FinancialEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncBankMovementExpenseLinks extends Command
{
    protected $signature = 'financial:sync-bank-expense-links
        {--company-id= : ID de empresa (obligatorio en producción)}
        {--dry-run : No escribe cambios}
        {--limit=0 : Limita movimientos procesados}';

    protected $description = 'Sincroniza gastos enlazados a movimientos bancarios: pagos, estado y método banco.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $companyId = $this->option('company-id');

        if ($companyId === null || $companyId === '') {
            $this->error('Debes indicar --company-id para evitar tocar datos de otras empresas.');
            return self::FAILURE;
        }
        $companyId = (int) $companyId;

        $hasPaymentsTable = Schema::hasTable('expense_payments');
        if (! $hasPaymentsTable) {
            $this->warn('La tabla expense_payments no existe. Se actualizará paid_amount en financial_entries, pero no habrá detalle de pagos.');
        }

        $q = BankMovement::query()
            ->whereNotNull('financial_entry_id')
            // Importante: corregir enlaces antiguos aunque is_conciliated esté a 0
            ->with(['financialEntry']);

        // Filtrar por empresa vía bank_accounts.company_id
        $q->whereHas('bankAccount', fn ($qa) => $qa->where('company_id', $companyId));

        if ($limit > 0) {
            $q->limit($limit);
        }

        $movements = $q->get();
        $total = $movements->count();
        if ($total === 0) {
            $this->info('No hay movimientos conciliados/enlazados para sincronizar.');
            return self::SUCCESS;
        }

        $touchedMovements = 0;
        $touchedEntries = 0;
        $createdPayments = 0;

        $this->info('Movimientos a revisar: '.$total.($dryRun ? ' (dry-run)' : ''));

        foreach ($movements as $movement) {
            $entry = $movement->financialEntry;
            if (! $entry || $entry->type !== 'expense') {
                continue;
            }

            $movementAmount = round(abs((float) ($movement->amount ?? 0)), 2);
            if ($movementAmount <= 0) {
                // Al menos fijar método banco
                if (($entry->expense_payment_method ?? null) !== 'bank') {
                    $touchedMovements++;
                    $touchedEntries++;
                    if (! $dryRun) {
                        $entry->forceFill(['expense_payment_method' => 'bank'])->save();
                    }
                }
                continue;
            }

            DB::transaction(function () use (
                $dryRun,
                $hasPaymentsTable,
                $movement,
                $entry,
                $movementAmount,
                &$touchedMovements,
                &$touchedEntries,
                &$createdPayments
            ) {
                $touchedMovements++;

                // Asegurar que el movimiento queda marcado como conciliado/enlazado
                if (! $dryRun) {
                    $movement->forceFill([
                        'is_conciliated' => true,
                        'status' => $movement->status ?: 'confirmado',
                        'financial_entry_id' => $entry->id,
                    ])->save();
                }

                // Crear pago (si procede) evitando duplicados simples (mismo día + importe + método + gasto)
                if ($hasPaymentsTable) {
                    $exists = ExpensePayment::query()
                        ->where('financial_entry_id', $entry->id)
                        ->where('method', 'bank')
                        ->where('date', $movement->date)
                        ->where('amount', $movementAmount)
                        ->exists();

                    if (! $exists) {
                        if (! $dryRun) {
                            $entry->expensePayments()->create([
                                'date' => $movement->date,
                                'method' => 'bank',
                                'amount' => $movementAmount,
                            ]);
                        }
                        $createdPayments++;
                    }

                    $totalPaid = (float) ExpensePayment::query()
                        ->where('financial_entry_id', $entry->id)
                        ->sum('amount');
                } else {
                    $totalPaid = (float) ($entry->paid_amount ?? 0) + $movementAmount;
                }

                $totalAmount = (float) ($entry->total_amount ?? $entry->expense_amount ?? $entry->amount ?? 0);
                $newStatus = ($totalAmount > 0 && $totalPaid >= $totalAmount) ? 'pagado' : 'pendiente';
                $paymentDate = null;
                if ($newStatus === 'pagado') {
                    $paymentDate = $hasPaymentsTable
                        ? (ExpensePayment::query()->where('financial_entry_id', $entry->id)->max('date') ?: $movement->date)
                        : $movement->date;
                }

                $touchedEntries++;
                if (! $dryRun) {
                    $entry->forceFill([
                        'expense_payment_method' => 'bank',
                        'paid_amount' => $totalPaid,
                        'status' => $newStatus,
                        'payment_date' => $paymentDate,
                    ])->save();
                }
            });
        }

        $this->line('---');
        $this->info('Movimientos tocados: '.$touchedMovements);
        $this->info('Gastos actualizados: '.$touchedEntries);
        $this->info('Pagos creados (bank): '.$createdPayments);

        if ($dryRun) {
            $this->comment('Dry-run: no se ha escrito nada. Ejecuta sin --dry-run para aplicar cambios.');
        }

        return self::SUCCESS;
    }
}

