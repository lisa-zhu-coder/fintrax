<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelongsToCompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Filtra los resultados por company_id de la sesión actual.
     * Si no hay company_id en sesión (super_admin sin empresa seleccionada),
     * no aplica filtro (deja que el middleware se encargue de redirigir).
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Obtener company_id de la sesión
        $companyId = session('company_id');

        // Solo aplicar filtro si hay company_id en sesión
        if ($companyId !== null) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
