<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Interceptar la creación de conexiones para asegurar que el prefijo sea una cadena
        $this->app->extend('db', function ($db) {
            $db->getEventDispatcher()->listen('connection.*', function ($event, $connection) {
                if (method_exists($connection, 'getTablePrefix')) {
                    $prefix = $connection->getTablePrefix();
                    if (!is_string($prefix)) {
                        $connection->setTablePrefix('');
                    }
                }
            });
            return $db;
        });
    }

    public function boot(): void
    {
        // Forzar HTTPS cuando no es entorno local (production, staging, Laravel Cloud, etc.)
        // Evita que POST /users y otros redirects vayan a http:// y se pierda la sesión
        $forceHttps = !$this->app->environment('local')
            || filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);
        if ($forceHttps) {
            URL::forceScheme('https');
        }

        // Compartir nombre de empresa con todas las vistas (para el sidebar, debajo de Fintrax)
        // Usa View Composer para obtener el nombre de la empresa activa desde la sesión
        View::composer('*', function ($view) {
            $companyId = session('company_id');
            $companyName = null;
            $company = null;

            if ($companyId) {
                // Buscar sin el Global Scope para evitar problemas de recursión
                $company = Company::withoutGlobalScopes()->find($companyId);
                $companyName = $company?->name;
            }
            
            $view->with('companyName', $companyName);
            $view->with('clientsModuleEnabled', $company ? (bool) ($company->clients_module_enabled ?? false) : false);
        });

        // Mapeo para relaciones polimórficas de Transfer
        // Esto permite usar 'store' y 'wallet' en lugar de nombres de clase completos
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'store' => \App\Models\Store::class,
            'wallet' => \App\Models\CashWallet::class,
        ]);
    }
}
