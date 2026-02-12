<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // producto compuesto
            $table->foreignId('ingredient_product_id')->constrained('products')->cascadeOnDelete(); // ingrediente
            $table->decimal('quantity_per_unit', 12, 4); // cantidad por 1 unidad vendida
            $table->string('unit', 20); // gr, ml, ud
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
