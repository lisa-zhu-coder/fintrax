<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_control_day_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_control_day_comments');
    }
};
