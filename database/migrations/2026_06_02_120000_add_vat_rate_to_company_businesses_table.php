<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('company_businesses', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->default(21)->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_businesses', function (Blueprint $table) {
            if (Schema::hasColumn('company_businesses', 'vat_rate')) {
                $table->dropColumn('vat_rate');
            }
        });
    }
};
