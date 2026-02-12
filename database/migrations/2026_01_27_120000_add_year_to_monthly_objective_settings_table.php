<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $currentYear = (int) date('Y');

        // MySQL requiere eliminar la FK antes de modificar el índice que la soporta
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });

        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'month']);
        });

        Schema::table('monthly_objective_settings', function (Blueprint $table) use ($currentYear) {
            $table->unsignedSmallInteger('year')->default($currentYear)->after('store_id');
        });

        \DB::table('monthly_objective_settings')->update(['year' => $currentYear]);

        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->unique(['store_id', 'year', 'month']);
        });

        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'year', 'month']);
        });
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropColumn('year');
        });
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->unique(['store_id', 'month']);
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }
};
