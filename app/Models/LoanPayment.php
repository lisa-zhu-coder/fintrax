<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    protected $fillable = [
        'loan_id',
        'date',
        'amount',
        'source',
        'bank_movement_id',
        'comment',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::deleting(function (LoanPayment $payment) {
            if ($payment->bank_movement_id) {
                BankMovement::where('id', $payment->bank_movement_id)->update(['is_conciliated' => false]);
            }
        });
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function bankMovement(): BelongsTo
    {
        return $this->belongsTo(BankMovement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
