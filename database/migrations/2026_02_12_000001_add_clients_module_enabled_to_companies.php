<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'clients_module_enabled')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('clients_module_enabled')->default(false);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'clients_module_enabled')) {
            return;
        }
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('clients_module_enabled');
        });
    }
};
