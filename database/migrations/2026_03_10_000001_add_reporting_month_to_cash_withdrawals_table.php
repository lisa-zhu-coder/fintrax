<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cash_withdrawals', 'reporting_month')) {
            Schema::table('cash_withdrawals', function (Blueprint $table) {
                $table->string('reporting_month', 7)->nullable()->after('date')->comment('Mes al que corresponde la recogida (YYYY-MM)');
            });
        }

        // Rellenar con el mes de la fecha para registros existentes
        $rows = DB::table('cash_withdrawals')->whereNull('reporting_month')->get();
        foreach ($rows as $row) {
            $month = \Carbon\Carbon::parse($row->date)->format('Y-m');
            DB::table('cash_withdrawals')->where('id', $row->id)->update(['reporting_month' => $month]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cash_withdrawals', 'reporting_month')) {
            Schema::table('cash_withdrawals', function (Blueprint $table) {
                $table->dropColumn('reporting_month');
            });
        }
    }
};
