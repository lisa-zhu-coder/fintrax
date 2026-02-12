<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar tipos antiguos (compras, mercaderia) al nuevo compras_mercaderia
        DB::table('suppliers')
            ->whereIn('type', ['compras', 'mercaderia'])
            ->update(['type' => 'compras_mercaderia']);
    }

    public function down(): void
    {
        // Revertir: compras_mercaderia -> mercaderia (no reversible con precisión)
        DB::table('suppliers')
            ->where('type', 'compras_mercaderia')
            ->update(['type' => 'mercaderia']);
    }
};
