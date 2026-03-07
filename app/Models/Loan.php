<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'loan_type_id',
        'direction',
        'principal_amount',
        'interest_rate',
        'interest_type',
        'interest_index',
        'interest_spread',
        'interest_floor',
        'interest_cap',
        'term_months',
        'start_date',
        'opening_fee',
        'settlement_frequency',
        'amortization_system',
        'created_by',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'interest_spread' => 'decimal:4',
        'interest_floor' => 'decimal:4',
        'interest_cap' => 'decimal:4',
        'opening_fee' => 'decimal:2',
        'start_date' => 'date',
        'term_months' => 'integer',
    ];

    /**
     * Setter: evita cadenas vacías en campos decimal/integer para no romper el cast.
     */
    public function setAttribute($key, $value)
    {
        $decimalKeys = ['interest_rate', 'opening_fee', 'interest_spread', 'interest_floor', 'interest_cap'];
        if (in_array($key, $decimalKeys, true) && $value === '') {
            $value = null;
        }
        if ($key === 'term_months' && $value === '') {
            $value = null;
        }
        return parent::setAttribute($key, $value);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class)->orderBy('date')->orderBy('id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('installment_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Total pagado (suma de amount de todos los pagos).
     */
    public function getTotalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Intereses acumulados (estimación simple si no hay cuotas; si hay cuotas se puede usar la suma de interest_amount pagadas).
     */
    public function getAccruedInterest(): float
    {
        $rate = $this->getEffectiveAnnualRate();
        if ($rate === null || $rate <= 0) {
            return 0.0;
        }
        $start = $this->start_date;
        if (!$start) {
            return 0.0;
        }
        $end = Carbon::today();
        if ($end->lt($start)) {
            return 0.0;
        }
        $months = $start->diffInMonths($end);
        $capital = (float) $this->principal_amount;

        return round($capital * ((float) $rate / 100) * ($months / 12), 2);
    }

    /**
     * Tasa anual efectiva: fijo = interest_rate; variable = índice + spread, con suelo/techo.
     */
    public function getEffectiveAnnualRate(?float $indexRate = null): ?float
    {
        if ($this->interest_type === 'fixed') {
            $rate = $this->interest_rate !== null ? (float) $this->interest_rate : null;
        } else {
            if ($indexRate === null && $this->interest_index) {
                $indexRate = InterestIndex::getRateFor($this->interest_index, $this->start_date);
            }
            $rate = $indexRate !== null ? $indexRate + (float) ($this->interest_spread ?? 0) : null;
        }
        if ($rate === null) {
            return null;
        }
        $floor = $this->interest_floor !== null ? (float) $this->interest_floor : null;
        $cap = $this->interest_cap !== null ? (float) $this->interest_cap : null;
        if ($floor !== null && $rate < $floor) {
            $rate = $floor;
        }
        if ($cap !== null && $rate > $cap) {
            $rate = $cap;
        }
        return $rate;
    }

    /**
     * Saldo pendiente: principal + opening_fee + intereses acumulados - total_pagado.
     * Compatible con préstamos sin tabla de amortización.
     */
    public function getBalance(): float
    {
        $base = (float) $this->principal_amount
            + (float) ($this->opening_fee ?? 0)
            + $this->getAccruedInterest()
            - $this->getTotalPaid();

        return round($base, 2);
    }

    /**
     * Capital pendiente según tabla de amortización: remaining_balance de la última cuota ya vencida, o principal si ninguna.
     */
    public function getOutstandingPrincipal(): float
    {
        $today = Carbon::today();
        $lastDue = $this->installments()->where('due_date', '<=', $today)->orderByDesc('due_date')->first();
        if ($lastDue) {
            return (float) $lastDue->remaining_balance;
        }
        return (float) $this->principal_amount;
    }

    /**
     * Número de cuotas restantes (pendientes de vencimiento).
     */
    public function getRemainingInstallmentsCount(): int
    {
        $today = Carbon::today();
        return $this->installments()->where('due_date', '>', $today)->count();
    }
}
