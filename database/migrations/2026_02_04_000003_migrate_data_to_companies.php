<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migra los datos existentes a la nueva estructura multiempresa.
     * 
     * 1. Crea una empresa principal desde la tabla 'company' existente
     * 2. Asigna company_id a todos los registros existentes
     */
    public function up(): void
    {
        // Obtener datos de la tabla 'company' existente (si existe)
        $existingCompany = null;
        if (Schema::hasTable('company')) {
            $existingCompany = DB::table('company')->first();
        }

        // Crear la empresa principal en la nueva tabla 'companies'
        $companyId = DB::table('companies')->insertGetId([
            'name' => $existingCompany->name ?? 'Empresa Principal',
            'cif' => $existingCompany->cif ?? null,
            'address' => $existingCompany->fiscal_street ?? null,
            'email' => $existingCompany->fiscal_email ?? null,
            'phone' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tablas a actualizar con company_id
        $tables = [
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

        // Asignar company_id a todos los registros existentes
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                DB::table($table)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);
            }
        }

        // Opcional: eliminar la tabla 'company' antigua (comentado por seguridad)
        // Schema::dropIfExists('company');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar todos los registros de companies (esto restablecerá company_id a null por CASCADE)
        DB::table('companies')->truncate();
    }
};
