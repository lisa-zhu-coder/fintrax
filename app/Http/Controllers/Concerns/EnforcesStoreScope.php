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
     * Aplica el filtro de tienda a la query. Si el usuario no es admin, fuerza store_id = su tienda.
     * NOTA: El filtro de empresa se aplica automáticamente vía Global Scope en los modelos.
     */
    protected function scopeStoreForCurrentUser(Builder $query, string $column = 'store_id'): Builder
    {
        $user = Auth::user();
        if (!$user) {
            return $query;
        }
        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where($column, $enforcedStoreId);
        }
        return $query;
    }

    /**
     * Devuelve el store_id que debe usarse para crear/actualizar.
     * No-admin: siempre el de su usuario. Admin: el que venga en $requestStoreId (o null).
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
        return $enforced !== null ? $enforced : null;
    }

    /**
     * Tiendas disponibles para el usuario actual (para selects, listados).
     * Admin/Super Admin: todas las tiendas de la empresa activa (filtradas por Global Scope).
     * No-admin: solo su tienda.
     */
    protected function storesForCurrentUser()
    {
        $user = Auth::user();
        if (!$user) {
            // Store::all() ya filtra por company_id vía Global Scope
            return Store::query()->get();
        }
        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId === null) {
            // Store::all() ya filtra por company_id vía Global Scope
            return Store::all();
        }
        return Store::where('id', $enforcedStoreId)->get();
    }

    /**
     * Comprueba que el usuario pueda acceder al recurso de la tienda dada.
     * Aborta 403 si no tiene acceso.
     * 
     * NOTA: También verifica que la tienda pertenezca a la empresa activa.
     */
    protected function authorizeStoreAccess(?int $storeId): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'No autenticado.');
        }

        // Verificar que la tienda pertenezca a la empresa activa
        if ($storeId !== null) {
            // Store::find() aplica el Global Scope, así que si no encuentra la tienda,
            // significa que no pertenece a la empresa activa
            $store = Store::find($storeId);
            if (!$store) {
                abort(403, 'No tienes acceso a los datos de esta tienda.');
            }
        }

        // Admin y Super Admin pueden acceder a cualquier tienda de su empresa
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return;
        }

        // Usuarios con tienda asignada (en empresa actual) solo pueden acceder a esa tienda
        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId !== null && (int) $enforcedStoreId !== (int) $storeId) {
            abort(403, 'No tienes acceso a los datos de esta tienda.');
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
