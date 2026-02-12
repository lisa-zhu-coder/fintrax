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
        Schema::create('declared_sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->decimal('bank_amount', 10, 2);
            $table->decimal('cash_amount', 10, 2);
            $table->decimal('cash_reduction_percent', 5, 2);
            $table->decimal('total_with_vat', 10, 2);
            $table->decimal('total_without_vat', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declared_sales');
    }
};
