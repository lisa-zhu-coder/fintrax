<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Procedencia del efectivo cuando method = cash: 'wallet' (cartera) o 'store' (tienda).
     */
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->string('cash_source', 20)->nullable()->after('amount')->comment('wallet|store cuando method=cash');
            $table->foreignId('cash_wallet_id')->nullable()->after('cash_source')->constrained('cash_wallets')->onDelete('set null');
            $table->foreignId('cash_store_id')->nullable()->after('cash_wallet_id')->constrained('stores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropForeign(['cash_wallet_id']);
            $table->dropForeign(['cash_store_id']);
            $table->dropColumn(['cash_source', 'cash_wallet_id', 'cash_store_id']);
        });
    }
};
