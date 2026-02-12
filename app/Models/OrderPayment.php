<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPayment extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'order_id',
        'date',
        'method',
        'amount',
        'cash_source',
        'cash_wallet_id',
        'cash_store_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cashWallet(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CashWallet::class, 'cash_wallet_id');
    }

    public function cashStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'cash_store_id');
    }
}
