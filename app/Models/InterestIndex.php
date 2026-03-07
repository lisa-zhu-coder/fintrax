<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterestIndex extends Model
{
    protected $fillable = ['index_name', 'date', 'rate'];

    protected $casts = [
        'date' => 'date',
        'rate' => 'decimal:4',
    ];

    /**
     * Obtiene la tasa más reciente para un índice hasta una fecha dada.
     */
    public static function getRateFor(string $indexName, ?\DateTimeInterface $onDate = null): ?float
    {
        $query = static::where('index_name', $indexName)->orderByDesc('date');
        if ($onDate !== null) {
            $query->where('date', '<=', $onDate);
        }
        $record = $query->first();

        return $record ? (float) $record->rate : null;
    }
}
