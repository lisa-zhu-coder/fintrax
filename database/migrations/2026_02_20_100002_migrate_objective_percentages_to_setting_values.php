<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $companies = DB::table('companies')->pluck('id');
        $objectiveIdsByCompany = [];

        foreach ($companies as $companyId) {
            $id1 = DB::table('objective_definitions')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Objetivo 1',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $id2 = DB::table('objective_definitions')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Objetivo 2',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $objectiveIdsByCompany[$companyId] = [$id1, $id2];
        }

        $firstCompanyId = $companies->first();
        $settings = DB::table('monthly_objective_settings')->get();

        foreach ($settings as $row) {
            $companyId = $row->store_id
                ? (DB::table('stores')->where('id', $row->store_id)->value('company_id')) ?? $firstCompanyId
                : $firstCompanyId;
            $objIds = $objectiveIdsByCompany[$companyId] ?? $objectiveIdsByCompany[$firstCompanyId] ?? null;
            if (!$objIds) {
                continue;
            }
            DB::table('monthly_objective_setting_values')->insert([
                [
                    'monthly_objective_setting_id' => $row->id,
                    'objective_definition_id' => $objIds[0],
                    'percentage' => $row->percentage_objective_1 ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'monthly_objective_setting_id' => $row->id,
                    'objective_definition_id' => $objIds[1],
                    'percentage' => $row->percentage_objective_2 ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->dropColumn(['percentage_objective_1', 'percentage_objective_2']);
        });
    }

    public function down(): void
    {
        Schema::table('monthly_objective_settings', function (Blueprint $table) {
            $table->decimal('percentage_objective_1', 10, 2)->default(0)->after('month');
            $table->decimal('percentage_objective_2', 10, 2)->default(0)->after('percentage_objective_1');
        });

        $settings = DB::table('monthly_objective_settings')->get();
        foreach ($settings as $setting) {
            $values = DB::table('monthly_objective_setting_values')
                ->where('monthly_objective_setting_id', $setting->id)
                ->orderBy('objective_definition_id')
                ->get();
            $pct1 = $values->get(0)->percentage ?? 0;
            $pct2 = $values->get(1)->percentage ?? 0;
            DB::table('monthly_objective_settings')->where('id', $setting->id)->update([
                'percentage_objective_1' => $pct1,
                'percentage_objective_2' => $pct2,
            ]);
        }
        DB::table('monthly_objective_setting_values')->truncate();
        DB::table('objective_definitions')->truncate();
    }
};
