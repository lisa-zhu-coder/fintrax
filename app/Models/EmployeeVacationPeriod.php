<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeVacationPeriod extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'year',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getDaysWorkedAttribute(): int
    {
        $start = $this->period_start;
        $end = $this->period_end ?? Carbon::createFromDate($this->year, 12, 31);
        return max(0, $start->diffInDays($end) + 1);
    }

    public function getVacationDaysGeneratedAttribute(): float
    {
        return round($this->days_worked * 30 / 365, 2);
    }
}
