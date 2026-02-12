<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashWithdrawal extends Model
{
    protected $fillable = [
        'date',
        'store_id',
        'cash_wallet_id',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function cashWallet(): BelongsTo
    {
        return $this->belongsTo(CashWallet::class);
    }
}
