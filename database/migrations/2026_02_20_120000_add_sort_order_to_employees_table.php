<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('company_id');
        });

        $rows = DB::table('employees')->orderBy('company_id')->orderBy('id')->get(['id', 'company_id']);
        $iByCompany = [];
        foreach ($rows as $row) {
            $cid = $row->company_id ?? 0;
            $iByCompany[$cid] = ($iByCompany[$cid] ?? 0) + 1;
            DB::table('employees')->where('id', $row->id)->update(['sort_order' => $iByCompany[$cid]]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
