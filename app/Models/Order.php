<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes, BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'date',
        'store_id',
        'supplier_id',
        'invoice_number',
        'order_number',
        'concept',
        'amount',
        'history',
        'store_split',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'history' => 'array',
        'store_split' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    /**
     * Obtener la factura asociada buscando por invoice_number
     */
    public function invoice()
    {
        if (empty($this->invoice_number)) {
            return null;
        }
        
        return Invoice::where('invoice_number', $this->invoice_number)->first();
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('amount');
    }

    public function getPendingAmountAttribute(): float
    {
        $amount = (float) $this->amount;
        $paid = (float) $this->total_paid;
        $pending = round($amount - $paid, 2);

        if (abs($amount) < 0.005) {
            return 0.0;
        }

        if ($amount >= 0) {
            return $pending > 0.009 ? $pending : 0.0;
        }

        return $pending < -0.009 ? $pending : 0.0;
    }

    public function getStatusAttribute(): string
    {
        $amount = (float) $this->amount;
        $paid = (float) $this->total_paid;

        if (abs($amount) < 0.005) {
            return 'pagado';
        }

        if ($amount >= 0) {
            return $paid >= $amount - 0.009 ? 'pagado' : 'pendiente';
        }

        return $paid <= $amount + 0.009 ? 'pagado' : 'pendiente';
    }

    public function splitTypeLabel(): string
    {
        $stores = $this->store_split['stores'] ?? null;

        return is_array($stores) && count($stores) > 1 ? 'Conjunto' : 'Individual';
    }

    public function originStoreId(): ?int
    {
        $split = $this->store_split;

        if (is_array($split) && ! empty($split['origin_store_id'])) {
            return (int) $split['origin_store_id'];
        }

        if (! is_array($split) || empty($split['stores']) || count($split['stores']) <= 1) {
            return $this->store_id;
        }

        $payment = $this->relationLoaded('payments')
            ? $this->payments->first(fn (OrderPayment $payment) => $payment->cash_store_id)
            : null;

        if ($payment?->cash_store_id) {
            return (int) $payment->cash_store_id;
        }

        return $this->store_id;
    }
}
