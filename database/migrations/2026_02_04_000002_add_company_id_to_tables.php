<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas principales que recibirán company_id directamente.
     */
    private array $tables = [
        'stores',
        'users',
        'employees',
        'orders',
        'financial_entries',
        'invoices',
        'cash_wallets',
        'bank_accounts',
        'transfers',
        'suppliers',
        'company_businesses',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('company_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('companies')
                        ->onDelete('cascade');
                    
                    $table->index('company_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign([$tableName . '_company_id_foreign']);
                    $table->dropIndex([$tableName . '_company_id_index']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
