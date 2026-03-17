<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsurePendingPayrollUploadsTable extends Command
{
    protected $signature = 'payroll:ensure-pending-table';
    protected $description = 'Crea la tabla pending_payroll_uploads si no existe (para nóminas pendientes en ventana emergente).';

    public function handle(): int
    {
        if (Schema::hasTable('pending_payroll_uploads')) {
            $this->info('La tabla pending_payroll_uploads ya existe.');
            return 0;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("CREATE TABLE pending_payroll_uploads (
                token VARCHAR(128) PRIMARY KEY,
                payload LONGTEXT NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                INDEX idx_expires_at (expires_at)
            )");
        } else {
            DB::statement("CREATE TABLE pending_payroll_uploads (
                token VARCHAR(128) PRIMARY KEY,
                payload TEXT NOT NULL,
                expires_at DATETIME NOT NULL
            )");
        }

        $this->info('Tabla pending_payroll_uploads creada correctamente.');
        return 0;
    }
}
