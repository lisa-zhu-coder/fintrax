<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'store_id',
        'bank_name',
        'iban',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function bankMovements(): HasMany
    {
        return $this->hasMany(BankMovement::class);
    }
}
