<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeSetting extends Model
{
    protected $fillable = [
        'employee_id',
        'overtime_type_id',
        'price_per_hour',
    ];

    protected $casts = [
        'price_per_hour' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeType(): BelongsTo
    {
        return $this->belongsTo(OvertimeType::class);
    }

    /**
     * Precio por hora para un empleado y tipo. Devuelve 0 si no hay configuración.
     */
    public static function getPriceForEmployeeAndType(int $employeeId, int $overtimeTypeId): float
    {
        $s = self::where('employee_id', $employeeId)
            ->where('overtime_type_id', $overtimeTypeId)
            ->first();
        return (float) ($s->price_per_hour ?? 0);
    }

    /**
     * Precios por tipo para un empleado. Devuelve ['type_id' => price, ...]
     */
    public static function getPricesByTypeForEmployee(int $employeeId): array
    {
        return self::where('employee_id', $employeeId)
            ->get()
            ->pluck('price_per_hour', 'overtime_type_id')
            ->map(fn ($p) => (float) $p)
            ->toArray();
    }
}
