<?php

namespace App\Console\Commands;

use App\Models\FinancialEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class GenerateDailyCloseEntries extends Command
{
    protected $signature = 'financial:generate-daily-close-entries';
    protected $description = 'Genera registros de ingresos y gastos para cierres diarios existentes que no los tienen';

    public function handle()
    {
        $this->info('Buscando cierres diarios sin registros relacionados...');
        
        $dailyCloses = FinancialEntry::where('type', 'daily_close')
            ->orderBy('date', 'desc')
            ->get();
        
        $processed = 0;
        $created = 0;
        
        foreach ($dailyCloses as $dailyClose) {
            $processed++;
            
            // Verificar si ya tiene registros relacionados
            $existingRelated = FinancialEntry::where('notes', 'like', "%Generado automáticamente desde cierre diario #{$dailyClose->id}%")
                ->where('id', '!=', $dailyClose->id)
                ->count();
            
            if ($existingRelated > 0) {
                $this->line("Cierre diario #{$dailyClose->id} ({$dailyClose->date}) ya tiene registros relacionados. Saltando...");
                continue;
            }
            
            // Calcular valores
            $cashCounted = $dailyClose->calculateCashTotal();
            $cashInitial = (float) ($dailyClose->cash_initial ?? 0);
            $cashExpenses = (float) ($dailyClose->cash_expenses ?? 0);
            $computedCashSales = round($cashCounted - $cashInitial + $cashExpenses, 2);
            $tpv = (float) ($dailyClose->tpv ?? 0);
            
            $storeId = $dailyClose->store_id;
            $date = $dailyClose->date;
            $userId = $dailyClose->created_by ?? 1; // Usar el creador del cierre o admin por defecto
            
            // 1. Ingreso de efectivo
            if ($computedCashSales > 0) {
                FinancialEntry::create([
                    'date' => $date,
                    'store_id' => $storeId,
                    'type' => 'income',
                    'income_amount' => $computedCashSales,
                    'income_category' => 'cierre_diario',
                    'income_concept' => 'Cierre diario - Efectivo',
                    'amount' => $computedCashSales,
                    'concept' => 'Cierre diario - Efectivo',
                    'notes' => "Generado automáticamente desde cierre diario #{$dailyClose->id}",
                    'created_by' => $userId,
                ]);
                $created++;
            }
            
            // 2. Ingreso de tarjeta
            if ($tpv > 0) {
                FinancialEntry::create([
                    'date' => $date,
                    'store_id' => $storeId,
                    'type' => 'income',
                    'income_amount' => $tpv,
                    'income_category' => 'cierre_diario',
                    'income_concept' => 'Cierre diario - Tarjeta',
                    'amount' => $tpv,
                    'concept' => 'Cierre diario - Tarjeta',
                    'notes' => "Generado automáticamente desde cierre diario #{$dailyClose->id}",
                    'created_by' => $userId,
                ]);
                $created++;
            }
            
            // 3. Gasto de efectivo
            if ($cashExpenses > 0) {
                FinancialEntry::create([
                    'date' => $date,
                    'store_id' => $storeId,
                    'type' => 'expense',
                    'expense_amount' => $cashExpenses,
                    'expense_category' => 'otros',
                    'expense_source' => 'cierre_diario',
                    'expense_concept' => 'Gastos del cierre diario',
                    'expense_payment_method' => 'cash',
                    'expense_paid_cash' => true,
                    'amount' => $cashExpenses,
                    'concept' => 'Gastos del cierre diario',
                    'notes' => "Generado automáticamente desde cierre diario #{$dailyClose->id}",
                    'created_by' => $userId,
                ]);
                $created++;
            }
            
            $this->info("Procesado cierre diario #{$dailyClose->id} ({$dailyClose->date}) - Tienda: {$dailyClose->store->name}");
        }
        
        $this->info("\n✅ Procesados {$processed} cierres diarios");
        $this->info("✅ Creados {$created} registros relacionados (ingresos y gastos)");
        
        return Command::SUCCESS;
    }
}
