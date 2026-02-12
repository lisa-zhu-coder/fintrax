<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RingInventory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'date',
        'shift',
        'initial_quantity',
        'tara_quantity',
        'sold_quantity',
        'final_quantity',
    ];

    protected $casts = [
        'date' => 'date',
        'initial_quantity' => 'integer',
        'tara_quantity' => 'integer',
        'sold_quantity' => 'integer',
        'final_quantity' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Discrepancia = initial_quantity + tara_quantity + sold_quantity - final_quantity
     * (null se considera 0).
     */
    public function getDiscrepancyAttribute(): int
    {
        $initial = $this->initial_quantity ?? 0;
        $tara = $this->tara_quantity ?? 0;
        $sold = $this->sold_quantity ?? 0;
        $final = $this->final_quantity ?? 0;
        return $initial + $tara + $sold - $final;
    }

    public static function shiftOptions(): array
    {
        return [
            'cambio_turno' => 'Cambio de turno',
            'cierre' => 'Cierre',
        ];
    }
}
