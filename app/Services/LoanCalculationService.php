<?php

namespace App\Services;

use App\Models\InterestIndex;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\Carbon;

class LoanCalculationService
{
    /**
     * Genera la tabla de amortización (cuotas) para un préstamo y las persiste en loan_installments.
     * Usa sistema francés y soporta interés fijo o variable (índice + diferencial, suelo/techo).
     *
     * @param  float|null  $variableRateOverride  Tasa del índice para variable (%). Si null, se busca en interest_indexes.
     */
    public function generateInstallments(Loan $loan, ?float $variableRateOverride = null): array
    {
        $loan->installments()->delete();

        $termMonths = (int) $loan->term_months;
        $startDate = $loan->start_date;
        if (!$termMonths || !$startDate) {
            return [];
        }

        $principal = (float) $loan->principal_amount;
        if ($principal <= 0) {
            return [];
        }

        $annualRate = $this->resolveAnnualRate($loan, $variableRateOverride);
        $monthlyRate = $annualRate / 100 / 12;

        $payment = $this->frenchPayment($principal, $monthlyRate, $termMonths);
        $payment = round($payment, 2);

        $dueDate = Carbon::parse($startDate);
        $remaining = $principal;
        $installments = [];

        for ($n = 1; $n <= $termMonths; $n++) {
            $interestAmount = round($remaining * $monthlyRate, 2);
            $principalAmount = round($payment - $interestAmount, 2);

            if ($n === $termMonths) {
                $principalAmount = round($remaining, 2);
                $payment = round($interestAmount + $principalAmount, 2);
            }

            $remaining = round($remaining - $principalAmount, 2);
            if ($remaining < 0) {
                $remaining = 0;
            }

            $installments[] = [
                'loan_id' => $loan->id,
                'installment_number' => $n,
                'due_date' => $dueDate->copy()->format('Y-m-d'),
                'payment_amount' => $payment,
                'interest_amount' => $interestAmount,
                'principal_amount' => $principalAmount,
                'remaining_balance' => $remaining,
            ];

            $dueDate->addMonth();
        }

        foreach ($installments as $row) {
            LoanInstallment::create($row);
        }

        return $installments;
    }

    /**
     * Calcula la cuota mensual según el sistema francés: P * (r * (1+r)^n) / ((1+r)^n - 1).
     */
    public function frenchPayment(float $principal, float $monthlyRate, int $termMonths): float
    {
        if ($monthlyRate <= 0) {
            return round($principal / $termMonths, 2);
        }
        $factor = (1 + $monthlyRate) ** $termMonths;

        return $principal * ($monthlyRate * $factor) / ($factor - 1);
    }

    /**
     * Resuelve la tasa anual efectiva (porcentaje): fijo = interest_rate; variable = índice + spread con suelo/techo.
     */
    public function resolveAnnualRate(Loan $loan, ?float $variableRateOverride = null): float
    {
        $rate = $loan->getEffectiveAnnualRate($variableRateOverride);
        if ($rate !== null) {
            return $rate;
        }
        return 0.0;
    }
}
