<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Employee::withoutGlobalScopes()
            ->orderBy('id')
            ->chunk(200, function ($employees) {
                foreach ($employees as $employee) {
                    $raw = (string) ($employee->getRawOriginal('full_name') ?? '');
                    $norm = Employee::normalizeFullName($raw);
                    if ($norm !== $raw && $norm !== '') {
                        DB::table('employees')->where('id', $employee->id)->update([
                            'full_name' => $norm,
                            'updated_at' => $employee->getRawOriginal('updated_at') ?? now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversible
    }
};
