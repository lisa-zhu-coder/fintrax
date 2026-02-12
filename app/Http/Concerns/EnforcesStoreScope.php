<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait para controladores que deben filtrar por tienda según el rol.
 * - Admin: puede ver todas las tiendas; store_id del request se respeta.
 * - No admin: SIEMPRE se filtra por store_id del usuario; se ignora store_id del request.
 */
trait EnforcesStoreScope
{
    /**
     * Aplica el filtro de tienda a la query. Si el usuario no es admin, fuerza store_id = su tienda.
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
     * No-admin: siempre el de su usuario. Admin/Super Admin: el que venga en $requestStoreId (o null).
     */
    protected function enforcedStoreIdForCreate(?int $requestStoreId = null): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return $requestStoreId;
        }
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $requestStoreId;
        }
        return $user->store_id ? (int) $user->store_id : null;
    }

    /**
     * Tiendas disponibles para el usuario actual (para selects, listados).
     * Admin: todas. No-admin: solo su tienda.
     */
    protected function storesForCurrentUser()
    {
        $user = Auth::user();
        if (!$user) {
            return Store::query()->get();
        }
        $enforcedStoreId = $user->getEnforcedStoreId();
        if ($enforcedStoreId === null) {
            return Store::all();
        }
        return Store::where('id', $enforcedStoreId)->get();
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
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return;
        }
        if ($storeId === null || (int) $user->store_id !== (int) $storeId) {
            abort(403, 'No tienes acceso a los datos de esta tienda.');
        }
    }
}
