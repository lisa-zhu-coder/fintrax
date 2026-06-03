<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declared_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('declared_sales', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->default(21)->after('cash_reduction_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('declared_sales', function (Blueprint $table) {
            if (Schema::hasColumn('declared_sales', 'vat_rate')) {
                $table->dropColumn('vat_rate');
            }
        });
    }
};
