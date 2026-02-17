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
        Schema::table('ring_inventories', function (Blueprint $table) {
            $table->integer('replenishment_quantity')->nullable()->after('initial_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ring_inventories', function (Blueprint $table) {
            $table->dropColumn('replenishment_quantity');
        });
    }
};
