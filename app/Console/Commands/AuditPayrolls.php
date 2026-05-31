<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditPayrolls extends Command
{
    protected $signature = 'payrolls:audit';

    protected $description = 'Lista nóminas cuyo PDF no se puede recuperar (sin archivo en disco ni copia en base64)';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $broken = 0;
        $ok = 0;

        Payroll::withTrashed()->orderBy('id')->chunk(100, function ($payrolls) use ($disk, &$broken, &$ok) {
            foreach ($payrolls as $payroll) {
                $hasFile = $payroll->file_path && $disk->exists($payroll->file_path);
                $hasBase64 = ! empty($payroll->base64_content);

                if ($hasFile || $hasBase64) {
                    $ok++;

                    continue;
                }

                $broken++;
                $this->line(sprintf(
                    'ID %d | empleado %d | %s | sin PDF',
                    $payroll->id,
                    $payroll->employee_id,
                    $payroll->file_name
                ));
            }
        });

        $this->newLine();
        $this->info("Nóminas recuperables: {$ok}");
        if ($broken > 0) {
            $this->warn("Nóminas rotas (eliminar y volver a subir): {$broken}");
        } else {
            $this->info('No hay nóminas rotas.');
        }

        return self::SUCCESS;
    }
}
