<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'subject',
        'body',
        'type',
    ];

    public const TYPE_PAYROLL = 'payroll';
    public const TYPE_DOCUMENT = 'document';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
