<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearAllPayrolls extends Command
{
    protected $signature = 'payroll:clear-all
                            {--force : Ejecutar sin pedir confirmación}';

    protected $description = 'Elimina todas las nóminas guardadas (registros y archivos en disco). Útil para limpiar producción.';

    public function handle(): int
    {
        $query = Payroll::withTrashed();
        $total = $query->count();
        if ($total === 0) {
            $this->info('No hay nóminas en la base de datos.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("Se eliminarán {$total} nómina(s) y sus archivos. ¿Continuar?")) {
            $this->info('Operación cancelada.');
            return 1;
        }

        $deleted = 0;
        Payroll::withTrashed()->orderBy('id')->chunk(100, function ($payrolls) use (&$deleted) {
            foreach ($payrolls as $payroll) {
                if ($payroll->file_path && Storage::disk('local')->exists($payroll->file_path)) {
                    Storage::disk('local')->delete($payroll->file_path);
                }
                $payroll->forceDelete();
                $deleted++;
            }
        });

        $this->info("Eliminadas {$deleted} nómina(s).");
        return 0;
    }
}
