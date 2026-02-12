<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_objective_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->string('month', 2); // 01, 02, ... 12
            $table->decimal('percentage_objective_1', 10, 2)->default(0);
            $table->decimal('percentage_objective_2', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_objective_settings');
    }
};
