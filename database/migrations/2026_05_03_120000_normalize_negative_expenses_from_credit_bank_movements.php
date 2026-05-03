<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gastos creados desde conciliación sobre movimientos crédito deben tener importe negativo (reducen el total de gastos).
     */
    public function up(): void
    {
        if (! Schema::hasTable('financial_entries') || ! Schema::hasTable('bank_movements')) {
            return;
        }

        $q = DB::table('financial_entries as fe')
            ->join('bank_movements as bm', 'bm.financial_entry_id', '=', 'fe.id')
            ->where('fe.type', 'expense')
            ->where('fe.expense_source', 'conciliacion_bancaria')
            ->where('bm.type', 'credit');

        if (Schema::hasColumn('financial_entries', 'deleted_at')) {
            $q->whereNull('fe.deleted_at');
        }

        $ids = $q->where(function ($sub) {
            $sub->where('fe.expense_amount', '>', 0)
                ->orWhere('fe.amount', '>', 0)
                ->orWhere('fe.total_amount', '>', 0)
                ->orWhere('fe.paid_amount', '>', 0);
        })
            ->distinct()
            ->pluck('fe.id');

        foreach ($ids as $id) {
            $row = DB::table('financial_entries')->where('id', $id)->first();
            if (! $row) {
                continue;
            }

            $base = (float) ($row->expense_amount ?: $row->amount ?: $row->total_amount ?: $row->paid_amount ?: 0);
            if ($base <= 0) {
                continue;
            }

            $neg = -abs($base);

            DB::table('financial_entries')->where('id', $id)->update([
                'expense_amount' => $neg,
                'amount' => $neg,
                'total_amount' => $neg,
                'paid_amount' => $neg,
            ]);
        }
    }

    /**
     * No revertir: no es posible saber con certeza qué filas eran positivas antes de la corrección.
     */
    public function down(): void
    {
        //
    }
};
