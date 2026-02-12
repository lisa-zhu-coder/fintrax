<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('type'); // credit o debit
            $table->boolean('is_conciliated')->default(false);
            $table->foreignId('financial_entry_id')->nullable()->constrained('financial_entries')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_movements');
    }
};
