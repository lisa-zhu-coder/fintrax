<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToCompany;
    
    /** Tipos de proveedor (coherentes con categorías de gasto) */
    public const TYPES = [
        'compras_mercaderia' => 'Compras / Mercadería',
        'suministros' => 'Suministros',
        'servicios_profesionales' => 'Servicios profesionales',
        'equipamiento' => 'Equipamiento',
        'otros' => 'Otros',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'cif',
        'address',
        'email',
        'phone',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }
}
