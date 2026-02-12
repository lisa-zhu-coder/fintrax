<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('inventory_base_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_base_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('initial_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_base_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('source_type'); // 'adjustments' | 'inventory'
            $table->unsignedBigInteger('source_inventory_id')->nullable();
            $table->timestamps();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreign('source_inventory_id')->references('id')->on('inventories')->nullOnDelete();
        });

        Schema::create('inventory_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_base_product_id')->constrained()->cascadeOnDelete();
            $table->integer('initial_quantity')->default(0);
            $table->integer('acquired_quantity')->default(0);
            $table->integer('sold_quantity')->default(0);
            $table->integer('real_quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lines');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('inventory_base_products');
        Schema::dropIfExists('inventory_bases');
    }
};
