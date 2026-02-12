<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_purchase_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_inventory_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->date('purchase_date');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchase_records');
    }
};
