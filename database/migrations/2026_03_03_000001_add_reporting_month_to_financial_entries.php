<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mes correspondiente para cálculo de beneficio (YYYY-MM).
     * Por defecto = mes de la fecha; editable en ingresos/gastos manuales.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('financial_entries', 'reporting_month')) {
            Schema::table('financial_entries', function (Blueprint $table) {
                $table->string('reporting_month', 7)->nullable()->after('date');
            });
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE financial_entries SET reporting_month = DATE_FORMAT(date, '%Y-%m') WHERE reporting_month IS NULL");
        } elseif ($driver === 'sqlite') {
            DB::statement("UPDATE financial_entries SET reporting_month = strftime('%Y-%m', date) WHERE reporting_month IS NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_entries', 'reporting_month')) {
            Schema::table('financial_entries', function (Blueprint $table) {
                $table->dropColumn('reporting_month');
            });
        }
    }
};
