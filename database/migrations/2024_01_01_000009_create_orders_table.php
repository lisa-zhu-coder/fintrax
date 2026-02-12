<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->string('invoice_number');
            $table->string('order_number');
            $table->enum('concept', ['pedido', 'royalty', 'rectificacion', 'tara']);
            $table->decimal('amount', 10, 2);
            $table->json('history')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
