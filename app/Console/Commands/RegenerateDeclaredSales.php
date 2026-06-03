<?php

namespace App\Console\Commands;

use App\Services\DeclaredSalesFromDailyClosesService;
use App\Support\StoreVatRates;
use Illuminate\Console\Command;

class RegenerateDeclaredSales extends Command
{
    protected $signature = 'declared-sales:regenerate
                            {--store= : ID o slug de la tienda}
                            {--business= : ID o slug del negocio (company_businesses)}
                            {--month= : Solo un mes Y-m (ej. 2025-03)}
                            {--dry-run : Lista meses y IVA sin guardar cambios}
                            {--force : Ejecutar sin pedir confirmación}';

    protected $description = 'Regenera ventas declaradas desde cierres diarios (recalcula IVA y totales).';

    public function handle(DeclaredSalesFromDailyClosesService $service): int
    {
        try {
            $stores = $service->resolveStores(
                $this->option('store'),
                $this->option('business')
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $storeIds = $stores->pluck('id')->all();
        $onlyMonth = $this->option('month');
        if ($onlyMonth !== null && ! preg_match('/^\d{4}-\d{2}$/', $onlyMonth)) {
            $this->error('Formato de --month inválido. Usa Y-m, por ejemplo 2025-06.');

            return self::FAILURE;
        }

        $months = $onlyMonth !== null
            ? [$onlyMonth]
            : $service->monthsToRegenerate($storeIds);

        if (empty($months)) {
            $this->warn('No hay meses con cierres diarios ni ventas declaradas para regenerar.');

            return self::SUCCESS;
        }

        foreach ($stores as $store) {
            $vat = StoreVatRates::forStore($store);
            $this->info("Tienda: {$store->name} (id {$store->id}, slug {$store->slug}) — IVA {$vat}%");
        }

        $this->line('Meses a procesar: ' . implode(', ', $months));

        if ($this->option('dry-run')) {
            $this->info('Dry run: no se ha guardado nada.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('¿Regenerar ventas declaradas para estos meses?')) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $result = $service->regenerateAllMonths($storeIds, $onlyMonth);

        $this->info(sprintf(
            'Listo. Actualizados: %d, creados: %d. Meses con cambios: %s',
            $result['updated'],
            $result['created'],
            $result['months'] !== [] ? implode(', ', $result['months']) : '(ninguno con cierres)'
        ));

        return self::SUCCESS;
    }
}
