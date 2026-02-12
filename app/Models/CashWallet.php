<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashWallet extends Model
{
    use BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'name',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(CashWalletExpense::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(CashWalletTransfer::class);
    }
}
