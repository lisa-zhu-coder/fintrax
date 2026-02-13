<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'daily_close_pos_label')) {
                $table->string('daily_close_pos_label', 100)->default('Sistema POS');
            }
            if (!Schema::hasColumn('companies', 'daily_close_pos_cash_label')) {
                $table->string('daily_close_pos_cash_label', 150)->default('Sistema POS · Efectivo (€)');
            }
            if (!Schema::hasColumn('companies', 'daily_close_pos_card_label')) {
                $table->string('daily_close_pos_card_label', 150)->default('Sistema POS · Tarjeta (€)');
            }
            if (!Schema::hasColumn('companies', 'daily_close_vouchers_enabled')) {
                $table->boolean('daily_close_vouchers_enabled')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'daily_close_pos_label',
                'daily_close_pos_cash_label',
                'daily_close_pos_card_label',
                'daily_close_vouchers_enabled',
            ]);
        });
    }
};
