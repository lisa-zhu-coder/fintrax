<?php

namespace App\Support;

use App\Models\CompanyBusiness;
use App\Models\Store;
use Illuminate\Support\Collection;

class StoreVatRates
{
    /** @var array<int, float> */
    private static array $byStoreId = [];

    /** @var array<string, float> */
    private static array $bySlug = [];

    public static function forStore(?Store $store): float
    {
        if (! $store) {
            return VatCalculator::DEFAULT_RATE;
        }

        if (isset(self::$byStoreId[$store->id])) {
            return self::$byStoreId[$store->id];
        }

        $rate = self::resolveBySlug($store->slug);
        self::$byStoreId[$store->id] = $rate;

        return $rate;
    }

    public static function forStoreId(int $storeId, ?string $slug = null): float
    {
        if (isset(self::$byStoreId[$storeId])) {
            return self::$byStoreId[$storeId];
        }

        if ($slug !== null) {
            $rate = self::resolveBySlug($slug);
            self::$byStoreId[$storeId] = $rate;

            return $rate;
        }

        $store = Store::withoutGlobalScopes()->find($storeId);

        return self::forStore($store);
    }

    /**
     * @param  Collection<int, Store>|array<int, Store>  $stores
     * @return array<int, float>
     */
    public static function mapForStores($stores): array
    {
        $map = [];
        foreach ($stores as $store) {
            $map[$store->id] = self::forStore($store);
        }

        return $map;
    }

    private static function resolveBySlug(?string $slug): float
    {
        if ($slug === null || $slug === '') {
            return VatCalculator::DEFAULT_RATE;
        }

        if (isset(self::$bySlug[$slug])) {
            return self::$bySlug[$slug];
        }

        $business = CompanyBusiness::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        $rate = $business !== null
            ? (float) ($business->vat_rate ?? VatCalculator::DEFAULT_RATE)
            : VatCalculator::DEFAULT_RATE;

        self::$bySlug[$slug] = $rate;

        return $rate;
    }
}
