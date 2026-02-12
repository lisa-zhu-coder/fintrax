<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeSetting extends Model
{
    protected $fillable = [
        'employee_id',
        'price_overtime_hour',
        'price_sunday_holiday_hour',
    ];

    protected $casts = [
        'price_overtime_hour' => 'decimal:2',
        'price_sunday_holiday_hour' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function getPriceForEmployee(int $employeeId): array
    {
        $s = self::where('employee_id', $employeeId)->first();
        return [
            (float) ($s->price_overtime_hour ?? 0),
            (float) ($s->price_sunday_holiday_hour ?? 0),
        ];
    }
}
