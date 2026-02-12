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
        Schema::create('cash_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->foreignId('cash_wallet_id')->constrained('cash_wallets')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_withdrawals');
    }
};
