<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month']);
        });

        Schema::create('sales_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_month_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->timestamps();

            $table->unique(['sales_month_id', 'week_number']);
        });

        Schema::create('weekly_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_week_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_sold')->default(0);
            $table->timestamps();

            $table->unique(['sales_week_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_sales');
        Schema::dropIfExists('sales_weeks');
        Schema::dropIfExists('sales_months');
    }
};
