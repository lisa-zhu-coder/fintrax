<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesMonth extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'year',
        'month',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function weeks(): HasMany
    {
        return $this->hasMany(SalesWeek::class, 'sales_month_id')->orderBy('week_number');
    }

    public function getLabelAttribute(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }
}
