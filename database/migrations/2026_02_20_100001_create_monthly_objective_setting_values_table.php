<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('monthly_objective_setting_values')) {
            return;
        }

        Schema::create('monthly_objective_setting_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_objective_setting_id')->constrained('monthly_objective_settings')->onDelete('cascade');
            $table->foreignId('objective_definition_id')->constrained('objective_definitions')->onDelete('cascade');
            $table->decimal('percentage', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['monthly_objective_setting_id', 'objective_definition_id'], 'mosv_setting_objective_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_objective_setting_values');
    }
};
