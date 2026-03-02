<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tipos por defecto para cada empresa que tenga empleados
        $companyIds = DB::table('employees')->distinct()->pluck('company_id')->filter();
        foreach ($companyIds as $companyId) {
            DB::table('overtime_types')->insert([
                ['company_id' => $companyId, 'name' => 'Horas extras', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['company_id' => $companyId, 'name' => 'Domingo/festivos', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 2. Nueva tabla overtime_settings (employee_id, overtime_type_id, price_per_hour)
        Schema::create('overtime_settings_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('overtime_type_id')->constrained('overtime_types')->onDelete('cascade');
            $table->decimal('price_per_hour', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'overtime_type_id']);
        });

        // Migrar datos de overtime_settings antigua
        $oldSettings = DB::table('overtime_settings')->get();
        foreach ($oldSettings as $s) {
            $employee = DB::table('employees')->find($s->employee_id);
            if (!$employee) continue;
            $types = DB::table('overtime_types')->where('company_id', $employee->company_id)->orderBy('sort_order')->get();
            if ($types->count() >= 1) {
                DB::table('overtime_settings_new')->insert([
                    'employee_id' => $s->employee_id,
                    'overtime_type_id' => $types[0]->id,
                    'price_per_hour' => $s->price_overtime_hour ?? 0,
                    'created_at' => $s->created_at ?? now(),
                    'updated_at' => $s->updated_at ?? now(),
                ]);
            }
            if ($types->count() >= 2) {
                DB::table('overtime_settings_new')->insert([
                    'employee_id' => $s->employee_id,
                    'overtime_type_id' => $types[1]->id,
                    'price_per_hour' => $s->price_sunday_holiday_hour ?? 0,
                    'created_at' => $s->created_at ?? now(),
                    'updated_at' => $s->updated_at ?? now(),
                ]);
            }
        }

        Schema::drop('overtime_settings');
        Schema::rename('overtime_settings_new', 'overtime_settings');

        // 3. Nueva tabla overtime_records (employee_id, date, overtime_type_id, hours)
        Schema::create('overtime_records_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->foreignId('overtime_type_id')->constrained('overtime_types')->onDelete('cascade');
            $table->decimal('hours', 8, 2)->default(0);
            $table->timestamps();
        });

        // Migrar registros antiguos
        $oldRecords = DB::table('overtime_records')->get();
        foreach ($oldRecords as $r) {
            $employee = DB::table('employees')->find($r->employee_id);
            if (!$employee) continue;
            $types = DB::table('overtime_types')->where('company_id', $employee->company_id)->orderBy('sort_order')->get();
            if ($types->count() >= 1 && ($r->overtime_hours ?? 0) > 0) {
                DB::table('overtime_records_new')->insert([
                    'employee_id' => $r->employee_id,
                    'date' => $r->date,
                    'overtime_type_id' => $types[0]->id,
                    'hours' => $r->overtime_hours,
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ]);
            }
            if ($types->count() >= 2 && ($r->sunday_holiday_hours ?? 0) > 0) {
                DB::table('overtime_records_new')->insert([
                    'employee_id' => $r->employee_id,
                    'date' => $r->date,
                    'overtime_type_id' => $types[1]->id,
                    'hours' => $r->sunday_holiday_hours,
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ]);
            }
        }

        Schema::drop('overtime_records');
        Schema::rename('overtime_records_new', 'overtime_records');
    }

    public function down(): void
    {
        // Recrear estructura antigua (simplificado; datos se pierden)
        Schema::create('overtime_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->onDelete('cascade');
            $table->decimal('price_overtime_hour', 10, 2)->default(0);
            $table->decimal('price_sunday_holiday_hour', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('sunday_holiday_hours', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::dropIfExists('overtime_types');
    }
};
