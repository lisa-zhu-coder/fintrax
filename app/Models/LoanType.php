<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'category',
        'has_interest',
        'has_opening_fee',
    ];

    protected $casts = [
        'has_interest' => 'boolean',
        'has_opening_fee' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Comprueba si este tipo está en uso por algún préstamo.
     */
    public function isInUse(): bool
    {
        return $this->loans()->exists();
    }
}
