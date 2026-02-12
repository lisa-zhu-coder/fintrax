<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objective_daily_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('month', 7); // YYYY-MM
            $table->date('date_2025');
            $table->date('date_2026');
            $table->string('weekday', 20);
            $table->decimal('base_2025', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'date_2026']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objective_daily_rows');
    }
};
