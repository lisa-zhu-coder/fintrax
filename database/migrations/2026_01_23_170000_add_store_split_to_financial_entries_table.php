<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->json('store_split')->nullable()->after('history');
        });
    }

    public function down()
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn('store_split');
        });
    }
};
