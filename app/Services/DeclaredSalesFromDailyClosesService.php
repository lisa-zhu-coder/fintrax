<?php

namespace App\Services;

use App\Models\DeclaredSale;
use App\Models\FinancialEntry;
use App\Models\Store;
use App\Models\StoreCashReduction;
use App\Support\StoreVatRates;
use App\Support\VatCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeclaredSalesFromDailyClosesService
{
    /**
     * Meses (Y-m) con cierres diarios o ventas declaradas existentes para las tiendas indicadas.
     *
     * @param  array<int>|null  $storeIds  null = todas las tiendas
     * @return array<int, string>
     */
    public function monthsToRegenerate(?array $storeIds = null): array
    {
        $months = collect();

        $closesQuery = FinancialEntry::query()
            ->where('type', 'daily_close');
        if ($storeIds !== null) {
            $closesQuery->whereIn('store_id', $storeIds);
        }
        foreach ($closesQuery->pluck('date') as $date) {
            $months->push(Carbon::parse($date)->format('Y-m'));
        }

        $salesQuery = DeclaredSale::query();
        if ($storeIds !== null) {
            $salesQuery->whereIn('store_id', $storeIds);
        }
        foreach ($salesQuery->pluck('date') as $date) {
            $months->push(Carbon::parse($date)->format('Y-m'));
        }

        return $months->unique()->sort()->values()->all();
    }

    /**
     * Regenera ventas declaradas desde cierres diarios para un mes.
     *
     * @param  array<int>|null  $storeIds  null = todas las tiendas con cierres en ese mes
     * @return array{updated: int, created: int, skipped: int, months: array<string>}
     */
    public function regenerateMonth(string $monthYm, ?array $storeIds = null): array
    {
        $month = Carbon::createFromFormat('Y-m', $monthYm)->startOfMonth();
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $query = FinancialEntry::where('type', 'daily_close')
            ->whereBetween('date', [$startDate, $endDate]);
        if ($storeIds !== null) {
            $query->whereIn('store_id', $storeIds);
        }

        $dailyCloses = $query->with('store')->get();
        if ($dailyCloses->isEmpty()) {
            $recalculated = $this->recalculateExistingDeclaredSales($monthYm, $storeIds);

            return [
                'updated' => $recalculated,
                'created' => 0,
                'skipped' => 0,
                'months' => $recalculated > 0 ? [$monthYm] : [],
            ];
        }

        $storeIdsInMonth = $dailyCloses->pluck('store_id')->unique()->all();
        $cashReductions = StoreCashReduction::whereIn('store_id', $storeIdsInMonth)
            ->get()
            ->keyBy('store_id');

        $updated = 0;
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($dailyCloses, $cashReductions, $startDate, &$updated, &$created, &$skipped) {
            foreach ($dailyCloses->groupBy('store_id') as $storeId => $closes) {
                $cashReduction = $cashReductions->get($storeId);
                $cashReductionPercent = $cashReduction ? (float) $cashReduction->cash_reduction_percent : 0;

                $bankAmount = 0;
                $cashAmount = 0;

                foreach ($closes as $close) {
                    $bankAmount += (float) ($close->tpv ?? 0);
                    $cashAmount += $close->calculateComputedCashSales();
                }

                $efectivoReducido = $cashAmount * (1 - ($cashReductionPercent / 100));
                $vatRate = StoreVatRates::forStoreId((int) $storeId, $closes->first()?->store?->slug);
                $totalWithVat = $bankAmount + $efectivoReducido;
                $totalWithoutVat = VatCalculator::amountWithoutVat($totalWithVat, $vatRate);

                $representativeDate = $startDate->copy();

                $declaredSale = DeclaredSale::where('store_id', $storeId)
                    ->whereYear('date', $representativeDate->year)
                    ->whereMonth('date', $representativeDate->month)
                    ->first();

                $payload = [
                    'date' => $representativeDate,
                    'bank_amount' => round($bankAmount, 2),
                    'cash_amount' => round($cashAmount, 2),
                    'cash_reduction_percent' => $cashReductionPercent,
                    'vat_rate' => $vatRate,
                    'total_with_vat' => round($totalWithVat, 2),
                    'total_without_vat' => round($totalWithoutVat, 2),
                ];

                if ($declaredSale) {
                    $declaredSale->update($payload);
                    $updated++;
                } else {
                    DeclaredSale::create(array_merge($payload, ['store_id' => $storeId]));
                    $created++;
                }
            }
        });

        return [
            'updated' => $updated,
            'created' => $created,
            'skipped' => $skipped,
            'months' => [$monthYm],
        ];
    }

    /**
     * @param  array<int>|null  $storeIds
     * @return array{updated: int, created: int, months: array<string>}
     */
    public function regenerateAllMonths(?array $storeIds = null, ?string $onlyMonth = null): array
    {
        $months = $onlyMonth !== null
            ? [$onlyMonth]
            : $this->monthsToRegenerate($storeIds);

        $totalUpdated = 0;
        $totalCreated = 0;
        $processedMonths = [];

        foreach ($months as $monthYm) {
            $result = $this->regenerateMonth($monthYm, $storeIds);
            $totalUpdated += $result['updated'];
            $totalCreated += $result['created'];
            if ($result['updated'] > 0 || $result['created'] > 0) {
                $processedMonths[] = $monthYm;
            }
        }

        return [
            'updated' => $totalUpdated,
            'created' => $totalCreated,
            'months' => $processedMonths,
        ];
    }

    /**
     * @return Collection<int, Store>
     */
    public function resolveStores(?string $storeOption, ?string $businessOption): Collection
    {
        if ($businessOption !== null) {
            $business = \App\Models\CompanyBusiness::withoutGlobalScopes()
                ->when(is_numeric($businessOption), fn ($q) => $q->where('id', (int) $businessOption))
                ->when(! is_numeric($businessOption), fn ($q) => $q->where('slug', $businessOption))
                ->first();

            if (! $business) {
                throw new \InvalidArgumentException("Negocio no encontrado: {$businessOption}");
            }

            $store = Store::withoutGlobalScopes()->where('slug', $business->slug)->first();
            if (! $store) {
                throw new \InvalidArgumentException("No hay tienda con slug «{$business->slug}» para el negocio «{$business->name}».");
            }

            return collect([$store]);
        }

        if ($storeOption !== null) {
            $store = Store::withoutGlobalScopes()
                ->when(is_numeric($storeOption), fn ($q) => $q->where('id', (int) $storeOption))
                ->when(! is_numeric($storeOption), fn ($q) => $q->where('slug', $storeOption))
                ->first();

            if (! $store) {
                throw new \InvalidArgumentException("Tienda no encontrada: {$storeOption}");
            }

            return collect([$store]);
        }

        throw new \InvalidArgumentException('Indica --store= o --business= (id o slug).');
    }

    /**
     * Actualiza IVA y total sin IVA en registros existentes cuando no hay cierres diarios.
     */
    private function recalculateExistingDeclaredSales(string $monthYm, ?array $storeIds): int
    {
        $month = Carbon::createFromFormat('Y-m', $monthYm)->startOfMonth();

        $query = DeclaredSale::query()
            ->with('store')
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month);

        if ($storeIds !== null) {
            $query->whereIn('store_id', $storeIds);
        }

        $updated = 0;
        foreach ($query->get() as $sale) {
            $vatRate = StoreVatRates::forStore($sale->store);
            $sale->update([
                'vat_rate' => $vatRate,
                'total_without_vat' => round(
                    VatCalculator::amountWithoutVat((float) $sale->total_with_vat, $vatRate),
                    2
                ),
            ]);
            $updated++;
        }

        return $updated;
    }
}
