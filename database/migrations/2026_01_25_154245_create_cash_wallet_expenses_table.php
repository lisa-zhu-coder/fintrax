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
        Schema::create('cash_wallet_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_wallet_id')->constrained('cash_wallets')->onDelete('cascade');
            $table->foreignId('financial_entry_id')->constrained('financial_entries')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_wallet_expenses');
    }
};
