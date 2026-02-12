<?php

use App\Models\ObjectiveDailyRow;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Corrige date_2025 en objective_daily_rows para la alineación correcta:
     * 02/01/2025 ↔ 01/01/2026, 03/01/2025 ↔ 02/01/2026, ..., 01/02/2025 ↔ 31/01/2026
     * Fórmula: date_2025 = date_2026 - 1 año + 1 día
     */
    public function up(): void
    {
        ObjectiveDailyRow::chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $date2026 = $row->date_2026 instanceof \Carbon\Carbon
                    ? $row->date_2026
                    : Carbon::parse($row->date_2026);
                $date2025 = $date2026->copy()->subYear()->addDay()->format('Y-m-d');
                $row->update(['date_2025' => $date2025]);
            }
        });
    }

    public function down(): void
    {
        // No reversible sin guardar los valores anteriores
    }
};
