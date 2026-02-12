<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tablas que necesitan deleted_at (excluyendo financial_entries que ya lo tiene y users que no usa SoftDeletes)
        $tables = [
            'stores',
            'roles',
            'company',
            'company_businesses',
            'employees',
            'payrolls',
            'orders',
            'order_payments',
            'expense_payments',
            'invoices',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'stores',
            'roles',
            'company',
            'company_businesses',
            'employees',
            'payrolls',
            'orders',
            'order_payments',
            'expense_payments',
            'invoices',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
