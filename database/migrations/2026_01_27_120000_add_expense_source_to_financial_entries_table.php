<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Procedencia del gasto (origen funcional): cierre_diario, control_efectivo,
     * cartera, gasto_manual, conciliacion_bancaria, pedido, factura.
     */
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->string('expense_source', 50)->nullable()->after('expense_concept')->comment('Procedencia funcional del gasto');
        });
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn('expense_source');
        });
    }
};
