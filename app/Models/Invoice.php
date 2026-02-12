<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes, BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'date',
        'invoice_number',
        'total_amount',
        'supplier_name',
        'details',
        'file_path',
        'payment_method',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relación con el usuario que creó la factura
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con los registros financieros asociados a esta factura
     */
    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class, 'invoice_id');
    }

    /**
     * Calcular el importe pendiente de la factura
     */
    public function getPendingAmountAttribute(): float
    {
        $totalAmount = (float) ($this->total_amount ?? 0);
        $paidAmount = (float) ($this->paid_amount ?? 0);
        return max(0, $totalAmount - $paidAmount);
    }
}
