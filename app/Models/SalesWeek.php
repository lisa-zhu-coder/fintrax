<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesWeek extends Model
{
    protected $fillable = [
        'sales_month_id',
        'week_number',
    ];

    protected $casts = [
        'week_number' => 'integer',
    ];

    public function salesMonth(): BelongsTo
    {
        return $this->belongsTo(SalesMonth::class);
    }

    public function weeklySales(): HasMany
    {
        return $this->hasMany(WeeklySale::class, 'sales_week_id');
    }

    public function getLabelAttribute(): string
    {
        return 'Semana ' . $this->week_number;
    }
}
