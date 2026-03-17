<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'month')) {
                $table->unsignedTinyInteger('month')->nullable()->after('date'); // 1-12
            }
            if (!Schema::hasColumn('payrolls', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('month');
            }
            if (!Schema::hasColumn('payrolls', 'file_path')) {
                $table->string('file_path')->nullable()->after('base64_content');
            }
            if (!Schema::hasColumn('payrolls', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('matched_by');
            }
            if (!Schema::hasColumn('payrolls', 'sent_by')) {
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete()->after('sent_at');
            }
        });

        // Backfill month/year from date for existing rows
        if (Schema::hasColumn('payrolls', 'date') && Schema::hasColumn('payrolls', 'month')) {
            $rows = \Illuminate\Support\Facades\DB::table('payrolls')->whereNull('month')->get();
            foreach ($rows as $row) {
                $d = \Carbon\Carbon::parse($row->date ?? now());
                \Illuminate\Support\Facades\DB::table('payrolls')->where('id', $row->id)->update([
                    'month' => $d->month,
                    'year' => $d->year,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'sent_by')) {
                $table->dropForeign(['sent_by']);
            }
            $cols = ['month', 'year', 'file_path', 'sent_at', 'sent_by'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('payrolls', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
