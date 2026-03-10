<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('financial_entries', 'source_income_id')) {
            return;
        }
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->foreignId('source_income_id')->nullable()->after('supplier_id')->constrained('financial_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropForeign(['source_income_id']);
        });
    }
};
