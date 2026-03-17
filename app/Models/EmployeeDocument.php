<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'title',
        'document_date',
        'file_path',
        'uploaded_by',
    ];

    protected $casts = [
        'document_date' => 'date',
    ];

    public const TYPES = [
        'contrato' => 'Contrato',
        'anexo' => 'Anexo',
        'DNI' => 'DNI',
        'certificado' => 'Certificado',
        'otros' => 'Otros',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
