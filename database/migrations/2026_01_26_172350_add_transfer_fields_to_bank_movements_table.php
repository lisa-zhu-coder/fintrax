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
        Schema::table('bank_movements', function (Blueprint $table) {
            $table->foreignId('destination_store_id')->nullable()->after('bank_account_id')->constrained('stores')->onDelete('set null');
            $table->string('status')->default('pendiente')->after('is_conciliated'); // pendiente, confirmado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_movements', function (Blueprint $table) {
            $table->dropForeign(['destination_store_id']);
            $table->dropColumn(['destination_store_id', 'status']);
        });
    }
};
