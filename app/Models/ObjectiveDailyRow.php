<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectiveDailyRow extends Model
{
    protected $fillable = [
        'store_id',
        'month',
        'date_2025',
        'date_2026',
        'weekday',
        'base_2025',
    ];

    protected $casts = [
        'date_2025' => 'date',
        'date_2026' => 'date',
        'base_2025' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
