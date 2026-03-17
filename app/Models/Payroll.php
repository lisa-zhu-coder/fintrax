<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'employee_id',
        'file_name',
        'date',
        'month',
        'year',
        'base64_content',
        'file_path',
        'matched_by',
        'sent_at',
        'sent_by',
    ];

    protected $casts = [
        'date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /** Estado: Pendiente o Enviado según sent_at */
    public function getStatusAttribute(): string
    {
        return $this->sent_at ? 'Enviado' : 'Pendiente';
    }
}
