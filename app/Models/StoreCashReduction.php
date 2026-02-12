<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCashReduction extends Model
{
    /**
     * Filtrar solo reducciones de tiendas de la empresa actual.
     */
    public function scopeForCurrentCompany(Builder $query): Builder
    {
        $companyId = session('company_id');
        if ($companyId === null) {
            return $query;
        }
        return $query->whereHas('store', fn ($q) => $q->where('company_id', $companyId));
    }

    protected $fillable = [
        'store_id',
        'cash_reduction_percent',
    ];

    protected $casts = [
        'cash_reduction_percent' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
