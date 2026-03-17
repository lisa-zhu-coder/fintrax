<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pending_payroll_uploads')) {
            return;
        }
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE pending_payroll_uploads MODIFY payload LONGTEXT');
    }

    public function down(): void
    {
        // optional: revert to json if needed
    }
};
