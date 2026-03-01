<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gastos creados desde control de efectivo o cartera están ya pagados en efectivo.
     * Corrige registros existentes que quedaron como pendientes.
     */
    public function up(): void
    {
        if (!Schema::hasTable('financial_entries') || !Schema::hasColumn('financial_entries', 'expense_source')) {
            return;
        }
        if (!Schema::hasColumn('financial_entries', 'status') || !Schema::hasColumn('financial_entries', 'paid_amount')) {
            return;
        }

        $update = [
            'status' => 'pagado',
            'paid_amount' => DB::raw('amount'),
        ];
        if (Schema::hasColumn('financial_entries', 'total_amount')) {
            $update['total_amount'] = DB::raw('COALESCE(NULLIF(total_amount, 0), amount)');
        }
        DB::table('financial_entries')
            ->where('type', 'expense')
            ->whereIn('expense_source', ['control_efectivo', 'cartera'])
            ->update($update);
    }

    public function down(): void
    {
        // No revertir: no sabemos qué registros estaban realmente pendientes
    }
};
