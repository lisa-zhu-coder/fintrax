<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('job_position_id')->nullable()->after('position')->constrained('job_positions')->nullOnDelete();
        });

        $rows = DB::table('employees')->select('id', 'company_id', 'position')
            ->whereNotNull('position')
            ->where('position', '!=', '')
            ->get();

        $idByCompanyName = [];
        foreach ($rows->groupBy('company_id') as $companyId => $group) {
            if ($companyId === null || $companyId === '') {
                continue;
            }
            $names = $group->pluck('position')->unique()->filter();
            $order = 0;
            foreach ($names as $name) {
                $exists = DB::table('job_positions')->where('company_id', $companyId)->where('name', $name)->first();
                if ($exists) {
                    $idByCompanyName[$companyId.'|'.$name] = $exists->id;
                } else {
                    $order++;
                    $id = DB::table('job_positions')->insertGetId([
                        'company_id' => $companyId,
                        'name' => $name,
                        'sort_order' => $order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $idByCompanyName[$companyId.'|'.$name] = $id;
                }
            }
        }

        foreach ($rows as $e) {
            $key = $e->company_id.'|'.$e->position;
            if (isset($idByCompanyName[$key])) {
                DB::table('employees')->where('id', $e->id)->update(['job_position_id' => $idByCompanyName[$key]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['job_position_id']);
            $table->dropColumn('job_position_id');
        });
        Schema::dropIfExists('job_positions');
    }
};
