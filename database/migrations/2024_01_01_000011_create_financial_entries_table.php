<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->enum('type', ['daily_close', 'expense', 'income', 'expense_refund']);
            $table->string('concept')->nullable();
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            
            // Campos específicos de cierre diario
            $table->decimal('cash_initial', 10, 2)->nullable();
            $table->decimal('tpv', 10, 2)->nullable();
            $table->decimal('cash_expenses', 10, 2)->nullable();
            $table->json('cash_count')->nullable(); // Conteo de monedas/billetes
            $table->decimal('shopify_cash', 10, 2)->nullable();
            $table->decimal('shopify_tpv', 10, 2)->nullable();
            $table->decimal('vouchers_in', 10, 2)->nullable();
            $table->decimal('vouchers_out', 10, 2)->nullable();
            $table->decimal('vouchers_result', 10, 2)->nullable();
            $table->json('expense_items')->nullable(); // Gastos detallados del cierre
            $table->decimal('sales', 10, 2)->nullable(); // Ventas totales calculadas
            $table->decimal('expenses', 10, 2)->nullable(); // Gastos totales
            
            // Campos para otros tipos
            $table->decimal('income_amount', 10, 2)->nullable();
            $table->string('income_category')->nullable();
            $table->string('income_concept')->nullable();
            $table->decimal('expense_amount', 10, 2)->nullable();
            $table->string('expense_category')->nullable();
            $table->enum('expense_payment_method', ['cash', 'bank', 'transfer', 'card'])->nullable();
            $table->string('expense_concept')->nullable();
            $table->boolean('expense_paid_cash')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_concept')->nullable();
            $table->string('refund_original_id')->nullable();
            $table->enum('refund_type', ['existing', 'new'])->nullable();
            
            $table->json('history')->nullable(); // Historial de cambios
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
    }
};
