<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpenseOrderSyncService
{
    /**
     * Asegura que un gasto con proveedor tenga un pedido asociado.
     * Si el gasto ya proviene de un pedido (notes contiene order_id), no crea nada.
     */
    public function ensureOrderForExpense(FinancialEntry $entry): void
    {
        if ($entry->type !== 'expense' || empty($entry->supplier_id)) {
            return;
        }

        $notes = $this->decodeEntryNotes($entry->notes);
        if (! empty($notes['order_id']) || ($entry->expense_source ?? '') === 'pedido') {
            return;
        }

        $amount = (float) ($entry->total_amount ?? $entry->expense_amount ?? $entry->amount ?? 0);
        $orderAmount = round(abs($amount), 2);
        if ($orderAmount <= 0) {
            return;
        }

        DB::transaction(function () use ($entry, $orderAmount) {
            $fresh = FinancialEntry::query()->lockForUpdate()->find($entry->id);
            if (! $fresh || $fresh->type !== 'expense' || empty($fresh->supplier_id)) {
                return;
            }
            $freshNotes = $this->decodeEntryNotes($fresh->notes);
            if (! empty($freshNotes['order_id']) || ($fresh->expense_source ?? '') === 'pedido') {
                return;
            }

            $concept = $fresh->expense_concept ?? $fresh->concept ?? 'Gasto';

            $order = Order::create([
                'company_id' => $fresh->company_id,
                'date' => $fresh->date,
                'store_id' => $fresh->store_id,
                'supplier_id' => $fresh->supplier_id,
                'concept' => 'pedido',
                'order_number' => 'AUTO-GASTO-'.$fresh->id,
                'invoice_number' => 'AUTO-GASTO-'.$fresh->id,
                'history' => [
                    [
                        'at' => now()->toIso8601String(),
                        'action' => 'created_from_expense',
                        'expense_id' => $fresh->id,
                        'expense_concept' => $concept,
                    ],
                ],
                'amount' => $orderAmount,
            ]);

            $this->syncOrderPaymentsFromExpense($order, $fresh);

            $freshNotes['order_id'] = $order->id;
            $freshNotes['order_created_from'] = 'expense';
            $fresh->forceFill(['notes' => json_encode($freshNotes)])->save();
        });
    }

    public function syncOrderPaymentsFromExpense(Order $order, FinancialEntry $expense): void
    {
        if ($expense->type !== 'expense') {
            return;
        }

        $payments = collect();
        try {
            if (Schema::hasTable('expense_payments')) {
                $payments = $expense->expensePayments()->get();
            }
        } catch (\Exception $e) {
            $payments = collect();
        }

        $mapMethod = function (?string $m): string {
            $m = strtolower((string) $m);

            return match ($m) {
                'cash' => 'cash',
                'card', 'tarjeta', 'datafono' => 'card',
                'bank', 'transfer' => 'transfer',
                default => 'transfer',
            };
        };

        if ($payments->isNotEmpty()) {
            foreach ($payments as $p) {
                $amt = round((float) ($p->amount ?? 0), 2);
                if ($amt <= 0) {
                    continue;
                }
                $order->payments()->create([
                    'date' => $p->date,
                    'method' => $mapMethod($p->method ?? null),
                    'amount' => $amt,
                ]);
            }

            return;
        }

        $paid = round((float) ($expense->paid_amount ?? 0), 2);
        if ($paid <= 0) {
            return;
        }

        $order->payments()->create([
            'date' => $expense->payment_date ?? $expense->date,
            'method' => $mapMethod($expense->expense_payment_method ?? null),
            'amount' => $paid,
        ]);
    }

    public function removeAutoOrderForExpense(FinancialEntry $expense): void
    {
        if ($expense->type !== 'expense') {
            return;
        }

        DB::transaction(function () use ($expense) {
            $fresh = FinancialEntry::query()->lockForUpdate()->find($expense->id);
            if (! $fresh || $fresh->type !== 'expense') {
                return;
            }

            $notes = $this->decodeEntryNotes($fresh->notes);
            $orderId = $notes['order_id'] ?? null;
            $createdFrom = $notes['order_created_from'] ?? null;

            if ($orderId && $createdFrom === 'expense') {
                Order::where('id', $orderId)->delete();
                unset($notes['order_id'], $notes['order_created_from']);
                $fresh->forceFill(['notes' => empty($notes) ? null : json_encode($notes)])->save();
            }
        });
    }

    public function syncAutoOrderForExpense(FinancialEntry $expense): void
    {
        if ($expense->type !== 'expense' || empty($expense->supplier_id)) {
            return;
        }

        $this->ensureOrderForExpense($expense);

        DB::transaction(function () use ($expense) {
            $fresh = FinancialEntry::query()->lockForUpdate()->find($expense->id);
            if (! $fresh || $fresh->type !== 'expense' || empty($fresh->supplier_id)) {
                return;
            }

            $notes = $this->decodeEntryNotes($fresh->notes);
            $orderId = $notes['order_id'] ?? null;
            $createdFrom = $notes['order_created_from'] ?? null;
            if (! $orderId || $createdFrom !== 'expense') {
                return;
            }

            $order = Order::find($orderId);
            if (! $order) {
                return;
            }

            $amount = (float) ($fresh->total_amount ?? $fresh->expense_amount ?? $fresh->amount ?? 0);
            $orderAmount = round(abs($amount), 2);

            $order->update([
                'company_id' => $fresh->company_id,
                'date' => $fresh->date,
                'store_id' => $fresh->store_id,
                'supplier_id' => $fresh->supplier_id,
                'amount' => $orderAmount,
            ]);

            $order->payments()->delete();
            $this->syncOrderPaymentsFromExpense($order, $fresh);
        });
    }

    /**
     * @return array{processed: int, created: int, updated: int, skipped: int}
     */
    public function syncAllExpensesWithSupplier(?int $companyId = null, bool $dryRun = false): array
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $query = FinancialEntry::withoutGlobalScopes()
            ->where('type', 'expense')
            ->whereNotNull('supplier_id')
            ->where('supplier_id', '>', 0)
            ->orderBy('id');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $query->chunkById(100, function ($entries) use ($dryRun, &$processed, &$created, &$updated, &$skipped) {
            foreach ($entries as $entry) {
                $processed++;
                $notes = $this->decodeEntryNotes($entry->notes);

                if (! empty($notes['order_id']) && ($notes['order_created_from'] ?? null) !== 'expense') {
                    $skipped++;

                    continue;
                }

                if (($entry->expense_source ?? '') === 'pedido') {
                    $skipped++;

                    continue;
                }

                $hadOrder = ! empty($notes['order_id']) && ($notes['order_created_from'] ?? null) === 'expense';

                if ($dryRun) {
                    $hadOrder ? $updated++ : $created++;

                    continue;
                }

                $beforeOrderId = $notes['order_id'] ?? null;
                $this->syncAutoOrderForExpense($entry);
                $entry->refresh();
                $afterNotes = $this->decodeEntryNotes($entry->notes);

                if (! $beforeOrderId && ! empty($afterNotes['order_id'])) {
                    $created++;
                } elseif ($beforeOrderId) {
                    $updated++;
                }
            }
        });

        return compact('processed', 'created', 'updated', 'skipped');
    }

    /**
     * @param  mixed  $notes
     * @return array<string, mixed>
     */
    public function decodeEntryNotes($notes): array
    {
        if ($notes === null || $notes === '') {
            return [];
        }
        if (is_array($notes)) {
            return $notes;
        }
        $decoded = @json_decode((string) $notes, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return ['text' => (string) $notes];
    }
}
