<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir columnas
        Schema::table('financial_entries', function (Blueprint $table) {
            // En SQLite, es más seguro añadir como nullable y luego rellenar
            // La validación NOT NULL se hará a nivel de aplicación
            $table->decimal('total_amount', 10, 2)->nullable()->default(0)->after('amount');
            $table->decimal('paid_amount', 10, 2)->default(0)->after('total_amount');
            $table->date('payment_date')->nullable()->after('paid_amount');
            $table->enum('status', ['pendiente', 'pagado'])->default('pendiente')->after('payment_date');
        });

        // Rellenar datos existentes
        // Para gastos: copiar amount a total_amount y establecer status según expense_paid_cash
        $expenses = DB::table('financial_entries')
            ->where('type', 'expense')
            ->get();

        foreach ($expenses as $expense) {
            $expenseAmount = $expense->expense_amount ?? $expense->amount ?? 0;
            $isPaid = $expense->expense_paid_cash ?? false;
            
            DB::table('financial_entries')
                ->where('id', $expense->id)
                ->update([
                    'total_amount' => $expenseAmount,
                    'paid_amount' => $isPaid ? $expenseAmount : 0,
                    'status' => $isPaid ? 'pagado' : 'pendiente',
                ]);
        }

        // Para otros tipos: copiar amount a total_amount
        $otherEntries = DB::table('financial_entries')
            ->where('type', '!=', 'expense')
            ->get();

        foreach ($otherEntries as $entry) {
            $amount = $entry->amount ?? 0;
            
            DB::table('financial_entries')
                ->where('id', $entry->id)
                ->update([
                    'total_amount' => $amount,
                    'paid_amount' => $amount,
                    'status' => 'pagado',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'paid_amount', 'payment_date', 'status']);
        });
    }
};
