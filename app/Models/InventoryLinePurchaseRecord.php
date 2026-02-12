<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLinePurchaseRecord extends Model
{
    protected $fillable = [
        'inventory_line_id',
        'quantity',
        'purchase_date',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'purchase_date' => 'date',
    ];

    public function inventoryLine(): BelongsTo
    {
        return $this->belongsTo(InventoryLine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
