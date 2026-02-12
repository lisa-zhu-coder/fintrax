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
            // Añadir transfer_id nullable para permitir que varios bank_movements se enlacen al mismo transfer
            $table->unsignedBigInteger('transfer_id')->nullable()->after('financial_entry_id');
            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('set null');
            $table->index('transfer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_movements', function (Blueprint $table) {
            $table->dropForeign(['transfer_id']);
            $table->dropIndex(['transfer_id']);
            $table->dropColumn('transfer_id');
        });
    }
};
