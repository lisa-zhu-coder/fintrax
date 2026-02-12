<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankMovement extends Model
{
    /**
     * Filtrar solo movimientos de cuentas bancarias de la empresa actual.
     */
    public function scopeForCurrentCompany(Builder $query): Builder
    {
        $companyId = session('company_id');
        if ($companyId === null) {
            return $query;
        }
        return $query->whereHas('bankAccount', fn ($q) => $q->where('company_id', $companyId));
    }
    protected $fillable = [
        'bank_account_id',
        'destination_store_id',
        'date',
        'description',
        'raw_description',
        'amount',
        'type',
        'is_conciliated',
        'status',
        'financial_entry_id',
        'transfer_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'is_conciliated' => 'boolean',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function financialEntry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class);
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }
}
