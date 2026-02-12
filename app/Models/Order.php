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
        return max(0, $this->amount - $this->total_paid);
    }

    public function getStatusAttribute(): string
    {
        return $this->pending_amount > 0 ? 'pendiente' : 'pagado';
    }
}
