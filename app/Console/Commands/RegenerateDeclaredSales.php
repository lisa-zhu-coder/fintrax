<?php

namespace App\Console\Commands;

use App\Models\CompanyBusiness;
use App\Services\DeclaredSalesFromDailyClosesService;
use App\Support\StoreVatRates;
use Illuminate\Console\Command;

class RegenerateDeclaredSales extends Command
{
    protected $signature = 'declared-sales:regenerate
                            {--store= : ID o slug de la tienda}
                            {--business= : ID o slug del negocio (company_businesses)}
                            {--month= : Solo un mes Y-m (ej. 2025-03)}
                            {--list : Muestra negocios disponibles (id, slug, nombre, IVA)}
                            {--dry-run : Lista meses y IVA sin guardar cambios}
                            {--force : Ejecutar sin pedir confirmación}';

    protected $description = 'Regenera ventas declaradas desde cierres diarios (recalcula IVA y totales).';

    public function handle(DeclaredSalesFromDailyClosesService $service): int
    {
        if ($this->option('list')) {
            $this->listBusinesses();

            return self::SUCCESS;
        }

        if ($this->option('business') === null && $this->option('store') === null) {
            $this->error('Indica --business= o --store= (id o slug real). Usa --list para ver los negocios.');
            $this->listBusinesses();

            return self::FAILURE;
        }

        try {
            $stores = $service->resolveStores(
                $this->option('store'),
                $this->option('business')
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            $this->newLine();
            $this->warn('Negocios en la base de datos:');
            $this->listBusinesses();

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

    private function listBusinesses(): void
    {
        $businesses = CompanyBusiness::withoutGlobalScopes()
            ->orderBy('company_id')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'slug', 'vat_rate']);

        if ($businesses->isEmpty()) {
            $this->warn('No hay negocios en company_businesses.');

            return;
        }

        $this->table(
            ['ID', 'Empresa', 'Slug (usar en --business=)', 'Nombre', 'IVA %'],
            $businesses->map(fn ($b) => [
                $b->id,
                $b->company_id,
                $b->slug,
                $b->name,
                $b->vat_rate ?? '21',
            ])->all()
        );

        $this->line('Ejemplo: php artisan declared-sales:regenerate --business=' . $businesses->first()->slug . ' --dry-run');
    }
}
