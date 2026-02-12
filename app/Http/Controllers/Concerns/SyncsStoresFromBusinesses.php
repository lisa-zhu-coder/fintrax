<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CompanyBusiness;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait SyncsStoresFromBusinesses
{
    /**
     * Sincroniza stores desde company_businesses
     * Asegura que cada negocio en company_businesses tenga su correspondiente Store
     */
    protected function syncStoresFromBusinesses()
    {
        try {
            $businesses = CompanyBusiness::all();
            foreach ($businesses as $business) {
                $slug = $business->slug;
                if (!$slug) {
                    $slug = Str::slug($business->name, '_');
                    $base = $slug;
                    $i = 1;
                    while (
                        CompanyBusiness::where('slug', $slug)->where('id', '!=', $business->id)->exists() ||
                        Store::where('slug', $slug)->exists()
                    ) {
                        $slug = $base . '_' . $i;
                        $i++;
                    }
                    $business->slug = $slug;
                    $business->save();
                }

                $store = Store::where('slug', $slug)->first();
                if (!$store) {
                    Store::create([
                        'name' => $business->name,
                        'slug' => $slug,
                    ]);
                } else {
                    // Mantener nombre sincronizado
                    if ($store->name !== $business->name) {
                        $store->name = $business->name;
                        $store->save();
                    }
                }
            }
        } catch (\Throwable $e) {
            // No bloquear la vista por un fallo de sincronización
            Log::warning('Error sincronizando stores desde businesses: ' . $e->getMessage());
        }
    }
}
