<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLine extends Model
{
    protected $fillable = [
        'inventory_id',
        'product_id',
        'initial_quantity',
        'acquired_quantity',
        'used_quantity',
        'sold_quantity',
        'real_quantity',
    ];

    protected $casts = [
        'initial_quantity' => 'integer',
        'acquired_quantity' => 'integer',
        'used_quantity' => 'integer',
        'sold_quantity' => 'integer',
        'real_quantity' => 'integer',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseRecords(): HasMany
    {
        return $this->hasMany(InventoryLinePurchaseRecord::class)->orderByDesc('purchase_date')->orderByDesc('created_at');
    }

    public function getExpectedQuantityAttribute(): int
    {
        return $this->initial_quantity + $this->acquired_quantity - $this->used_quantity - $this->sold_quantity;
    }

    public function getDiscrepancyAttribute(): int
    {
        return $this->real_quantity - $this->expected_quantity;
    }
}
