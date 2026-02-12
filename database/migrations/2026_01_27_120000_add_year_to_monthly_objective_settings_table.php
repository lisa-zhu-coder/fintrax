<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $currentYear = (int) date('Y');
        $hasYearColumn = Schema::hasColumn('monthly_objective_settings', 'year');

        if (!$hasYearColumn) {
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
        }

        // Completar índices y FK si faltan (recuperación de ejecución parcial)
        $indexes = \DB::select("SHOW INDEX FROM monthly_objective_settings WHERE Key_name = 'monthly_objective_settings_store_id_year_month_unique'");
        if (empty($indexes)) {
            Schema::table('monthly_objective_settings', function (Blueprint $table) {
                $table->unique(['store_id', 'year', 'month']);
            });
        }

        $foreignKeys = \DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'monthly_objective_settings'
            AND COLUMN_NAME = 'store_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if (empty($foreignKeys)) {
            Schema::table('monthly_objective_settings', function (Blueprint $table) {
                $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            });
        }
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
