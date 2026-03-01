<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * base64_content como TEXT (64 KB) no basta para una página PDF en base64.
     * LONGTEXT permite hasta 4 GB y evita el error 500 al subir nóminas.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payrolls MODIFY base64_content LONGTEXT NOT NULL');
        }
        // PostgreSQL TEXT ya permite contenido largo; SQLite no distingue LONGTEXT.
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payrolls MODIFY base64_content TEXT NOT NULL');
        }
    }
};
