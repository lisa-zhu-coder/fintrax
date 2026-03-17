<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'fiscal_street')) {
                $table->string('fiscal_street')->nullable()->after('cif');
            }
            if (!Schema::hasColumn('companies', 'fiscal_postal_code')) {
                $table->string('fiscal_postal_code', 10)->nullable()->after('fiscal_street');
            }
            if (!Schema::hasColumn('companies', 'fiscal_city')) {
                $table->string('fiscal_city')->nullable()->after('fiscal_postal_code');
            }
            if (!Schema::hasColumn('companies', 'fiscal_email')) {
                $table->string('fiscal_email')->nullable()->after('fiscal_city');
            }
        });

        // Rellenar desde address/email si existen y los fiscales están vacíos
        if (Schema::hasColumn('companies', 'address') && Schema::hasColumn('companies', 'fiscal_street')) {
            DB::table('companies')->whereNull('fiscal_street')->whereNotNull('address')->update(['fiscal_street' => DB::raw('address')]);
        }
        if (Schema::hasColumn('companies', 'email') && Schema::hasColumn('companies', 'fiscal_email')) {
            DB::table('companies')->whereNull('fiscal_email')->whereNotNull('email')->update(['fiscal_email' => DB::raw('email')]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $cols = ['fiscal_street', 'fiscal_postal_code', 'fiscal_city', 'fiscal_email'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
