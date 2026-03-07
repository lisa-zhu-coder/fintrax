<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'payment_amount',
        'interest_amount',
        'principal_amount',
        'remaining_balance',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
