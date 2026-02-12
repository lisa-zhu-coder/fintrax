<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashWalletExpense extends Model
{
    protected $fillable = [
        'cash_wallet_id',
        'financial_entry_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashWallet(): BelongsTo
    {
        return $this->belongsTo(CashWallet::class);
    }

    public function financialEntry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class);
    }
}
