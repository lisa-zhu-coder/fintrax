<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ring_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('shift', 20); // cambio_turno | cierre
            $table->integer('initial_quantity')->nullable();
            $table->integer('tara_quantity')->nullable();
            $table->integer('sold_quantity')->nullable();
            $table->integer('final_quantity')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ring_inventories');
    }
};
