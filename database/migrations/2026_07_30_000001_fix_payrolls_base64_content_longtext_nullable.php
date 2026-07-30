<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La migración 2026_03_21_100004 pasó base64_content a TEXT (64 KB) al hacerla nullable,
     * revirtiendo LONGTEXT. Los PDF en base64 suelen superar ese límite y provocan error 1406.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payrolls') || ! Schema::hasColumn('payrolls', 'base64_content')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payrolls MODIFY base64_content LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payrolls') || ! Schema::hasColumn('payrolls', 'base64_content')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payrolls MODIFY base64_content TEXT NULL');
        }
    }
};
