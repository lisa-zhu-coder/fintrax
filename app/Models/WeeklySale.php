<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklySale extends Model
{
    protected $fillable = [
        'sales_week_id',
        'product_id',
        'quantity_sold',
    ];

    protected $casts = [
        'quantity_sold' => 'integer',
    ];

    public function salesWeek(): BelongsTo
    {
        return $this->belongsTo(SalesWeek::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
