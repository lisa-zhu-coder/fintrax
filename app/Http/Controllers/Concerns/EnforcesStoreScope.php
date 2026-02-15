<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait para controladores que deben filtrar por tienda y empresa según el rol.
 * 
 * MULTIEMPRESA:
 * - El Global Scope BelongsToCompany en Store filtra automáticamente por session('company_id')
 * - Los métodos de este trait respetan ese filtro
 * 
 * TIENDAS:
 * - Admin/Super Admin: puede ver todas las tiendas de su empresa; store_id del request se respeta.
 * - No admin: SIEMPRE se filtra por store_id del usuario; se ignora store_id del request.
 */
trait EnforcesStoreScope
{
    /**
     * Aplica el filtro de tienda a la query.
     * - Usuario con una tienda: fuerza esa tienda.
     * - Usuario con varias tiendas: si $requestStoreId viene, filtra por esa (validando acceso); si no, filtra por todas las suyas.
     * - Admin: si $requestStoreId viene, filtra por esa; si no, sin filtro (todas).
     */
    protected function scopeStoreForCurrentUser(Builder $query, string $column = 'store_id', ?int $requestStoreId = null): Builder
    {
        $user = Auth::user();
        if (!$user) {
            return $query;
        }
        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where($column, $enforcedStoreId);
            return $query;
        }
        $allowed = $user->getAllowedStoreIds();
        if (!empty($allowed)) {
            if ($requestStoreId !== null && $requestStoreId !== 0) {
                if (!$user->canAccessStore($requestStoreId)) {
                    abort(403, 'No tienes acceso a los datos de esta tienda.');
                }
                $query->where($column, $requestStoreId);
            } else {
                $query->whereIn($column, $allowed);
            }
        }
        return $query;
    }

    /**
     * Devuelve el store_id que debe usarse para crear/actualizar.
     * No-admin con una tienda: siempre esa. No-admin con varias: el de $requestStoreId si está en allowed.
     * Admin: el que venga en $requestStoreId (o null).
     */
    protected function enforcedStoreIdForCreate(?int $requestStoreId = null): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return $requestStoreId;
        }
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return $requestStoreId;
        }
        $enforced = $user->getEnforcedStoreId();
        if ($enforced !== null) {
            return $enforced;
        }
        $allowed = $user->getAllowedStoreIds();
        if (!empty($allowed) && $requestStoreId !== null && $user->canAccessStore($requestStoreId)) {
            return $requestStoreId;
        }
        return !empty($allowed) ? $allowed[0] : null;
    }

    /**
     * Tiendas disponibles para el usuario actual (para selects, listados).
     * Admin/Super Admin: todas. No-admin: sus tiendas permitidas (una o varias).
     */
    protected function storesForCurrentUser()
    {
        $user = Auth::user();
        if (!$user) {
            return Store::query()->get();
        }
        $allowed = $user->getAllowedStoreIds();
        if (empty($allowed)) {
            return Store::all();
        }
        return Store::whereIn('id', $allowed)->get();
    }

    /**
     * Comprueba que el usuario pueda acceder al recurso de la tienda dada.
     * Aborta 403 si no tiene acceso.
     */
    protected function authorizeStoreAccess(?int $storeId): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'No autenticado.');
        }
        if ($storeId !== null) {
            $store = Store::find($storeId);
            if (!$store) {
                abort(403, 'No tienes acceso a los datos de esta tienda.');
            }
            if (!$user->canAccessStore($storeId)) {
                abort(403, 'No tienes acceso a los datos de esta tienda.');
            }
        }
    }

    /**
     * Obtiene el company_id actual de la sesión.
     */
    protected function currentCompanyId(): ?int
    {
        return session('company_id');
    }
}
