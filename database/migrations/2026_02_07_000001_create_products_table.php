<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_direct_sale')->default(false);
            $table->boolean('is_ingredient')->default(false);
            $table->string('stock_unit')->nullable(); // kg, l, ud
            $table->string('consumption_unit')->nullable(); // g, ml, ud
            $table->decimal('conversion_factor', 12, 4)->nullable(); // ej: 1 kg = 1000 g
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
