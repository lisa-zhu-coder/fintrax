<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Models\Scopes\BelongsToCompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait para modelos que pertenecen a una empresa.
 * 
 * Aplica automáticamente un Global Scope que filtra por company_id de la sesión.
 * También proporciona la relación company() y asigna automáticamente company_id al crear.
 */
trait BelongsToCompany
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToCompany(): void
    {
        // Aplicar el Global Scope
        static::addGlobalScope(new BelongsToCompanyScope());

        // Asignar automáticamente company_id al crear
        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = session('company_id');
            }
        });
    }

    /**
     * Empresa a la que pertenece este modelo.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope para consultar sin el filtro de empresa.
     * Útil para super_admin o consultas administrativas.
     */
    public function scopeWithoutCompanyScope($query)
    {
        return $query->withoutGlobalScope(BelongsToCompanyScope::class);
    }
}
