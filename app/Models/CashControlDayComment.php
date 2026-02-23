<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashControlDayComment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'store_id',
        'date',
        'comment',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
