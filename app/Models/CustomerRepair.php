<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerRepair extends Model
{
    use SoftDeletes, BelongsToCompany;

    public const STATUS_PENDING = 'pending';
    public const STATUS_FIXED = 'fixed';
    public const STATUS_NOTIFIED = 'notified';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_FIXED => 'Arreglado',
            self::STATUS_NOTIFIED => 'Avisado',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'bg-amber-100 text-amber-800',
            self::STATUS_FIXED => 'bg-blue-100 text-blue-800',
            self::STATUS_NOTIFIED => 'bg-violet-100 text-violet-800',
            self::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-800',
            self::STATUS_CANCELLED => 'bg-slate-200 text-slate-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    protected $fillable = [
        'company_id',
        'store_id',
        'date',
        'client_name',
        'phone',
        'article',
        'sku',
        'reason',
        'status',
        'notification_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'notification_date' => 'date',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
