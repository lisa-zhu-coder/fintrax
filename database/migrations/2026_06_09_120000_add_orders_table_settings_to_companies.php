<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'orders_table_settings')) {
                $table->json('orders_table_settings')->nullable()->after('daily_close_vouchers_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'orders_table_settings')) {
                $table->dropColumn('orders_table_settings');
            }
        });
    }
};
