<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeclaredSale extends Model
{
    protected $fillable = [
        'date',
        'store_id',
        'bank_amount',
        'cash_amount',
        'cash_reduction_percent',
        'vat_rate',
        'total_with_vat',
        'total_without_vat',
    ];

    protected $casts = [
        'date' => 'date',
        'bank_amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'cash_reduction_percent' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'total_with_vat' => 'decimal:2',
        'total_without_vat' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
