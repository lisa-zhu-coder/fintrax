<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use App\Models\ExpensePayment;

class FinancialEntry extends Model
{
    use SoftDeletes, BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'date',
        'store_id',
        'type',
        'concept',
        'amount',
        'notes',
        'cash_initial',
        'tpv',
        'cash_expenses',
        'cash_count',
        'shopify_cash',
        'shopify_tpv',
        'vouchers_in',
        'vouchers_out',
        'vouchers_result',
        'expense_items',
        'sales',
        'expenses',
        'income_amount',
        'income_category',
        'income_concept',
        'expense_amount',
        'expense_category',
        'expense_source',
        'expense_payment_method',
        'expense_concept',
        'expense_paid_cash',
        'refund_amount',
        'refund_concept',
        'refund_original_id',
        'refund_type',
        'history',
        'store_split',
        'created_by',
        'cash_real',
        'total_amount',
        'paid_amount',
        'payment_date',
        'status',
        'invoice_id',
        'supplier_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'cash_initial' => 'decimal:2',
        'tpv' => 'decimal:2',
        'cash_expenses' => 'decimal:2',
        'cash_count' => 'array',
        'shopify_cash' => 'decimal:2',
        'shopify_tpv' => 'decimal:2',
        'vouchers_in' => 'decimal:2',
        'vouchers_out' => 'decimal:2',
        'vouchers_result' => 'decimal:2',
        'expense_items' => 'array',
        'sales' => 'decimal:2',
        'expenses' => 'decimal:2',
        'income_amount' => 'decimal:2',
        'expense_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'expense_paid_cash' => 'boolean',
        'history' => 'array',
        'store_split' => 'array',
        'cash_real' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        // Asegurar que total_amount siempre tenga un valor antes de guardar
        static::saving(function ($entry) {
            if ($entry->type === 'expense' && empty($entry->total_amount)) {
                $entry->total_amount = $entry->expense_amount ?? $entry->amount ?? 0;
            } elseif (empty($entry->total_amount)) {
                $entry->total_amount = $entry->amount ?? 0;
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expensePayments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class, 'financial_entry_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function getTotalPaidAttribute(): float
    {
        if ($this->type === 'expense') {
            try {
                // Verificar si la tabla existe antes de intentar acceder
                if (Schema::hasTable('expense_payments')) {
                    // Verificar si la relación está cargada
                    if ($this->relationLoaded('expensePayments')) {
                        return (float) $this->getRelation('expensePayments')->sum('amount');
                    }
                    // Si no está cargada, intentar consultar directamente
                    $payments = ExpensePayment::where('financial_entry_id', $this->id)->get();
                    if ($payments->count() > 0) {
                        return (float) $payments->sum('amount');
                    }
                }
            } catch (\Exception $e) {
                // La tabla aún no existe o hay un error, usar paid_amount
            }
        }
        return (float) ($this->paid_amount ?? 0);
    }

    public function getPendingAmountAttribute(): float
    {
        $totalAmount = (float) ($this->total_amount ?? 0);
        $paidAmount = (float) ($this->paid_amount ?? 0);
        return max(0, $totalAmount - $paidAmount);
    }

    public function calculateCashTotal(): float
    {
        if (!$this->cash_count || !is_array($this->cash_count)) {
            return 0;
        }

        $total = 0;
        foreach ($this->cash_count as $denomination => $count) {
            $total += (float) $denomination * (int) $count;
        }

        return round($total, 2);
    }

    public function calculateComputedCashSales(): float
    {
        if ($this->type !== 'daily_close') {
            return 0;
        }

        $cashCounted = $this->calculateCashTotal();
        $cashInitial = (float) ($this->cash_initial ?? 0);
        $cashExpenses = (float) ($this->cash_expenses ?? 0);

        return round($cashCounted - $cashInitial + $cashExpenses, 2);
    }

    public function calculateTotalSales(): float
    {
        if ($this->type !== 'daily_close') {
            return (float) ($this->amount ?? 0);
        }

        $tpv = (float) ($this->tpv ?? 0);
        $computedCashSales = $this->calculateComputedCashSales();
        $vouchersResult = (float) ($this->vouchers_result ?? 0);

        return round($tpv + $computedCashSales + $vouchersResult, 2);
    }

    public function calculateCashDiscrepancy(): ?float
    {
        if ($this->type !== 'daily_close' || $this->shopify_cash === null) {
            return null;
        }

        $computedCashSales = $this->calculateComputedCashSales();
        return round($computedCashSales - (float) $this->shopify_cash, 2);
    }

    public function calculateTpvDiscrepancy(): ?float
    {
        if ($this->type !== 'daily_close' || $this->shopify_tpv === null) {
            return null;
        }

        $tpv = (float) ($this->tpv ?? 0);
        return round($tpv - (float) $this->shopify_tpv, 2);
    }
}
