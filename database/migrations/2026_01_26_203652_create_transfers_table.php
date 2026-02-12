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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->string('origin_type'); // 'store' o 'wallet'
            $table->unsignedBigInteger('origin_id');
            $table->string('origin_fund'); // 'cash' o 'bank'
            $table->string('destination_type'); // 'store' o 'wallet'
            $table->unsignedBigInteger('destination_id');
            $table->string('destination_fund'); // 'cash' o 'bank'
            $table->string('method')->default('manual'); // 'manual' o 'bank_import'
            $table->string('status')->default('pending'); // 'pending' o 'reconciled'
            $table->foreignId('bank_movement_id')->nullable()->constrained('bank_movements')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar rendimiento
            $table->index(['origin_type', 'origin_id']);
            $table->index(['destination_type', 'destination_id']);
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
