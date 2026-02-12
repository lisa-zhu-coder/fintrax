<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyObjectiveSetting extends Model
{
    protected $fillable = [
        'store_id',
        'year',
        'month',
        'percentage_objective_1',
        'percentage_objective_2',
    ];

    protected $casts = [
        'percentage_objective_1' => 'decimal:2',
        'percentage_objective_2' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Obtener porcentajes para una tienda, año y mes (MM).
     * Prioridad: configuración tienda+año+mes → genérica año+mes → 0.
     */
    public static function getPercentagesForStoreMonth(?int $storeId, string $month, ?int $year = null): array
    {
        $month = str_pad((string) (int) $month, 2, '0', STR_PAD_LEFT);
        $year = $year ?? (int) date('Y');
        $specific = null;
        $generic = null;
        if ($storeId !== null) {
            $specific = self::where('store_id', $storeId)->where('year', $year)->where('month', $month)->first();
        }
        $generic = self::whereNull('store_id')->where('year', $year)->where('month', $month)->first();
        $row = $specific ?? $generic;
        if (! $row) {
            return [0, 0];
        }
        return [
            (float) $row->percentage_objective_1,
            (float) $row->percentage_objective_2,
        ];
    }
}
