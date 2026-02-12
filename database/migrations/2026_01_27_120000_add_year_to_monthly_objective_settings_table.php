<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $currentYear = (int) date('Y');

        Schema::table('monthly_objective_settings', function (Blueprint $table) use ($currentYear) {
            $table->unsignedSmallInteger('year')->default($currentYear)->after('store_id');
        });

        \DB::table('monthly_objective_settings')->update(['year' => $currentYear]);

        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'month']);
        });

        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->unique(['store_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'year', 'month']);
        });
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->unique(['store_id', 'month']);
        });
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};
