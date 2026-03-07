<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('principal_amount', 12, 2)->default(0)->after('direction');
            $table->string('interest_index', 50)->nullable()->after('interest_type');
            $table->decimal('interest_spread', 8, 4)->nullable()->after('interest_index');
            $table->decimal('interest_floor', 8, 4)->nullable()->after('interest_spread');
            $table->decimal('interest_cap', 8, 4)->nullable()->after('interest_floor');
            $table->string('settlement_frequency', 20)->default('monthly')->after('opening_fee');
            $table->string('amortization_system', 20)->default('french')->after('settlement_frequency');
        });

        DB::table('loans')->update([
            'principal_amount' => DB::raw('initial_amount'),
        ]);

        DB::table('loans')->where('direction', 'they_owe_us')->update(['direction' => 'company_lends']);

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('initial_amount');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('initial_amount', 12, 2)->default(0)->after('direction');
        });
        DB::table('loans')->update(['initial_amount' => DB::raw('principal_amount')]);
        DB::table('loans')->where('direction', 'company_lends')->update(['direction' => 'they_owe_us']);
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'principal_amount', 'interest_index', 'interest_spread', 'interest_floor', 'interest_cap',
                'settlement_frequency', 'amortization_system',
            ]);
        });
    }
};
