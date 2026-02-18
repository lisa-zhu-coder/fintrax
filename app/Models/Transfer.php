<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Transfer extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'date',
        'amount',
        'origin_type',
        'origin_id',
        'origin_fund',
        'destination_type',
        'destination_id',
        'destination_fund',
        'method',
        'status',
        'applied_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    /**
     * Relación polimórfica con el origen (Store o CashWallet)
     */
    public function origin(): MorphTo
    {
        return $this->morphTo('origin');
    }

    /**
     * Relación polimórfica con el destino (Store o CashWallet)
     */
    public function destination(): MorphTo
    {
        return $this->morphTo('destination');
    }

    /**
     * Relación con los movimientos bancarios asociados (un transfer puede tener varios bank_movements)
     */
    public function bankMovements(): HasMany
    {
        return $this->hasMany(BankMovement::class);
    }

    /**
     * Relación con el usuario que creó la transferencia
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Aplicar la transferencia: crear los registros necesarios para afectar los saldos
     * IMPORTANTE: Este método NO actualiza el status. El status debe ser 'reconciled' ANTES de llamar a este método.
     * 
     * @return array ['success' => bool, 'message' => string|null]
     */
    public function apply(): array
    {
        // Verificar que el status sea 'reconciled' (debe haberse actualizado antes de llamar a apply)
        if ($this->status !== 'reconciled') {
            $message = 'El estado de la transferencia debe ser "reconciled" antes de aplicar. Estado actual: ' . $this->status;
            Log::warning('Intento de aplicar transferencia con estado incorrecto', [
                'transfer_id' => $this->id,
                'current_status' => $this->status
            ]);
            return ['success' => false, 'message' => $message];
        }

        // Evitar doble aplicación: comprobar applied_at y/o applied_records
        if ($this->applied_at !== null) {
            $message = 'Esta transferencia ya ha sido aplicada anteriormente (applied_at).';
            Log::warning('Intento de aplicar transferencia ya aplicada', ['transfer_id' => $this->id]);
            return ['success' => false, 'message' => $message];
        }
        $notes = json_decode($this->notes ?? '{}', true);
        if (isset($notes['applied_records'])) {
            $message = 'Esta transferencia ya ha sido aplicada anteriormente.';
            Log::warning('Intento de aplicar transferencia ya aplicada', ['transfer_id' => $this->id]);
            return ['success' => false, 'message' => $message];
        }

        // Validar saldo antes de aplicar (excluir bank_movements ya enlazados a este transfer)
        $firstBankMovement = $this->bankMovements()->first();
        $excludeBankMovementId = $firstBankMovement ? $firstBankMovement->id : null;
        $balanceValidation = $this->validateOriginBalance($excludeBankMovementId);
        if (!$balanceValidation['valid']) {
            return ['success' => false, 'message' => $balanceValidation['message']];
        }

        DB::beginTransaction();
        try {
            $createdRecords = [
                'origin' => null,
                'destination' => null,
            ];

            // Aplicar efectos en el origen (restar)
            $originResult = $this->applyOriginEffects();
            if ($originResult === null && $this->needsOriginEffect()) {
                throw new \Exception('Error al crear el registro de origen. No se pudo aplicar la transferencia.');
            }
            $createdRecords['origin'] = $originResult;

            // Aplicar efectos en el destino (sumar)
            $destinationResult = $this->applyDestinationEffects();
            if ($destinationResult === null && $this->needsDestinationEffect()) {
                throw new \Exception('Error al crear el registro de destino. No se pudo aplicar la transferencia.');
            }
            $createdRecords['destination'] = $destinationResult;

            // Guardar los IDs de los registros creados en notes para poder revertirlos
            $notes['applied_records'] = $createdRecords;
            $this->update([
                'notes' => json_encode($notes),
                'applied_at' => now(),
            ]);

            DB::commit();
            return ['success' => true, 'message' => null];
        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Error al aplicar la transferencia: ' . $e->getMessage();
            Log::error('Error al aplicar transferencia', [
                'transfer_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $message];
        }
    }

    /**
     * Revertir la transferencia: eliminar o revertir los registros creados
     * IMPORTANTE: Este método NO actualiza el status. El status debe actualizarse después de llamar a este método.
     * 
     * @return array ['success' => bool, 'message' => string|null]
     */
    public function rollback(): array
    {
        if ($this->status !== 'reconciled') {
            $message = 'Solo se pueden revertir transferencias con estado "reconciled". Estado actual: ' . $this->status;
            Log::warning('Intento de revertir transferencia no reconciliada', ['transfer_id' => $this->id]);
            return ['success' => false, 'message' => $message];
        }

        // Verificar que haya sido aplicada (applied_at o applied_records)
        $notes = json_decode($this->notes ?? '{}', true);
        if ($this->applied_at === null && !isset($notes['applied_records'])) {
            $message = 'No se encontraron registros aplicados para revertir.';
            Log::warning('Intento de revertir transferencia sin registros aplicados', ['transfer_id' => $this->id]);
            return ['success' => false, 'message' => $message];
        }

        DB::beginTransaction();
        try {
            // Revertir efectos en el destino (restar lo que se sumó)
            $this->rollbackDestinationEffects();

            // Revertir efectos en el origen (sumar lo que se restó)
            $this->rollbackOriginEffects();

            // Limpiar applied_at y los IDs de registros aplicados (el status se actualizará en el controlador)
            unset($notes['applied_records']);
            $this->update([
                'notes' => json_encode($notes),
                'applied_at' => null,
            ]);

            DB::commit();
            return ['success' => true, 'message' => null];
        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Error al revertir la transferencia: ' . $e->getMessage();
            Log::error('Error al revertir transferencia', [
                'transfer_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $message];
        }
    }

    /**
     * Aplicar efectos en el origen (restar del saldo)
     * @return array|null IDs de los registros creados
     */
    protected function applyOriginEffects(): ?array
    {
        $createdRecord = null;
        
        if ($this->origin_type === 'store') {
            if ($this->origin_fund === 'cash') {
                // Store + cash: NO crear expense. Los traspasos solo mueven saldos y se registran en transfers.
                // El saldo de efectivo se calcula en calculateStoreCashBalance() usando la tabla transfers.
                $createdRecord = null;
            } elseif ($this->origin_fund === 'bank') {
                // Store + bank: para method=bank_import NO crear BankMovement; los movimientos ya existen (importados).
                // El saldo se refleja en calculateStoreBankBalance vía los Transfer reconciliados.
                if ($this->method === 'bank_import') {
                    $createdRecord = null; // No crear movimientos fantasma; usar solo los enlazados (transfer_id).
                } else {
                    $bankAccount = BankAccount::where('store_id', $this->origin_id)->first();
                    if ($bankAccount) {
                        $movement = BankMovement::create([
                            'bank_account_id' => $bankAccount->id,
                            'date' => $this->date,
                            'description' => 'Transferencia a ' . $this->getDestinationName(),
                            'raw_description' => 'Transferencia a ' . $this->getDestinationName(),
                            'amount' => $this->amount,
                            'type' => 'debit',
                            'is_conciliated' => true,
                            'status' => 'conciliado',
                        ]);
                        $createdRecord = ['type' => 'bank_movement', 'id' => $movement->id];
                    }
                }
            }
        } elseif ($this->origin_type === 'wallet') {
            // Wallet + cash: crear CashWalletExpense para restar del saldo de la cartera
            // EXCEPCIÓN: Si el destino es store + bank y el método es 'manual',
            // no crear CashWalletExpense porque ya existe CashWalletTransfer que resta de la cartera
            if ($this->destination_type === 'store' && $this->destination_fund === 'bank' && $this->method === 'manual') {
                // Verificar si existe un CashWalletTransfer asociado (para "Ingresar efectivo")
                $existingTransfer = \App\Models\CashWalletTransfer::where('cash_wallet_id', $this->origin_id)
                    ->where('store_id', $this->destination_id)
                    ->where('date', $this->date)
                    ->where('amount', $this->amount)
                    ->first();
                
                if ($existingTransfer) {
                    // Ya existe CashWalletTransfer, no crear CashWalletExpense
                    // Solo crear el efecto en el destino (banco)
                    $createdRecord = null; // No hay registro creado en el origen
                } else {
                    // No existe CashWalletTransfer, crear CashWalletExpense normalmente
                    $expense = CashWalletExpense::create([
                        'cash_wallet_id' => $this->origin_id,
                        'store_id' => $this->destination_type === 'store' ? $this->destination_id : null,
                        'date' => $this->date,
                        'concept' => 'Transferencia a ' . $this->getDestinationName(),
                        'amount' => $this->amount,
                        'created_by' => $this->created_by,
                    ]);
                    $createdRecord = ['type' => 'cash_wallet_expense', 'id' => $expense->id];
                }
            } else {
                // Caso normal: crear CashWalletExpense
                $expense = CashWalletExpense::create([
                    'cash_wallet_id' => $this->origin_id,
                    'store_id' => $this->destination_type === 'store' ? $this->destination_id : null,
                    'date' => $this->date,
                    'concept' => 'Transferencia a ' . $this->getDestinationName(),
                    'amount' => $this->amount,
                    'created_by' => $this->created_by,
                ]);
                $createdRecord = ['type' => 'cash_wallet_expense', 'id' => $expense->id];
            }
        }
        
        return $createdRecord;
    }

    /**
     * Aplicar efectos en el destino (sumar al saldo)
     * @return array|null IDs de los registros creados
     */
    protected function applyDestinationEffects(): ?array
    {
        $createdRecord = null;
        
        if ($this->destination_type === 'store') {
            if ($this->destination_fund === 'cash') {
                // Store + cash: NO crear income. Los traspasos no son ingresos ni gastos.
                // El saldo de efectivo se calcula en calculateStoreCashBalance() usando la tabla transfers.
                $createdRecord = null;
            } elseif ($this->destination_fund === 'bank') {
                // Store + bank: para method=bank_import NO crear BankMovement; los movimientos ya existen (importados).
                if ($this->method === 'bank_import') {
                    $createdRecord = null; // No crear movimientos fantasma; usar solo los enlazados (transfer_id).
                } else {
                    $bankAccount = BankAccount::where('store_id', $this->destination_id)->first();
                    if (!$bankAccount) {
                        $storeName = $this->getDestinationName();
                        throw new \Exception("La tienda de destino ({$storeName}) no tiene una cuenta bancaria configurada. Configura la cuenta bancaria en la ficha de la tienda antes de ingresar efectivo en banco.");
                    }
                    $movement = BankMovement::create([
                        'bank_account_id' => $bankAccount->id,
                        'date' => $this->date,
                        'description' => 'Transferencia de ' . $this->getOriginName(),
                        'raw_description' => 'Transferencia de ' . $this->getOriginName(),
                        'amount' => $this->amount,
                        'type' => 'credit',
                        'is_conciliated' => true,
                        'status' => 'conciliado',
                    ]);
                    $createdRecord = ['type' => 'bank_movement', 'id' => $movement->id];
                }
            }
        } elseif ($this->destination_type === 'wallet') {
            // Wallet + cash: crear CashWithdrawal para sumar al saldo de la cartera
            $withdrawal = CashWithdrawal::create([
                'cash_wallet_id' => $this->destination_id,
                'store_id' => $this->origin_type === 'store' ? $this->origin_id : null,
                'date' => $this->date,
                'amount' => $this->amount,
                'created_by' => $this->created_by,
            ]);
            $createdRecord = ['type' => 'cash_withdrawal', 'id' => $withdrawal->id];
        }
        
        return $createdRecord;
    }

    /**
     * Revertir efectos en el origen (sumar lo que se restó)
     */
    protected function rollbackOriginEffects(): void
    {
        $notes = json_decode($this->notes ?? '{}', true);
        $appliedRecords = $notes['applied_records'] ?? null;
        
        if ($appliedRecords && isset($appliedRecords['origin'])) {
            $record = $appliedRecords['origin'];
            if ($record && isset($record['type']) && isset($record['id'])) {
                switch ($record['type']) {
                    case 'financial_entry':
                        FinancialEntry::where('id', $record['id'])->delete();
                        break;
                    case 'bank_movement':
                        BankMovement::where('id', $record['id'])->delete();
                        break;
                    case 'cash_wallet_expense':
                        CashWalletExpense::where('id', $record['id'])->delete();
                        break;
                }
                return;
            }
        }
        
        // Fallback: búsqueda por características si no hay IDs guardados
        if ($this->origin_type === 'store') {
            if ($this->origin_fund === 'cash') {
                FinancialEntry::where('notes', 'like', '%"transfer_id":' . $this->id . '%')
                    ->where('notes', 'like', '%"direction":"outgoing"%')
                    ->where('type', 'expense')
                    ->delete();
            } elseif ($this->origin_fund === 'bank') {
                BankMovement::where('description', 'like', '%Transferencia a%')
                    ->where('type', 'debit')
                    ->where('date', $this->date)
                    ->where('amount', $this->amount)
                    ->delete();
            }
        } elseif ($this->origin_type === 'wallet') {
            // cash_wallet_expenses no tiene 'concept' ni 'date'; solo se borra por ID vía applied_records.
            // Para ingreso a banco (wallet→store bank manual) no se crea CashWalletExpense, sino CashWalletTransfer,
            // por lo que no hay nada que revertir aquí (el CashWalletTransfer se elimina al borrar desde historial o al borrar el Transfer en el controlador).
        }
    }

    /**
     * Revertir efectos en el destino (restar lo que se sumó)
     */
    protected function rollbackDestinationEffects(): void
    {
        $notes = json_decode($this->notes ?? '{}', true);
        $appliedRecords = $notes['applied_records'] ?? null;
        
        if ($appliedRecords && isset($appliedRecords['destination'])) {
            $record = $appliedRecords['destination'];
            if ($record && isset($record['type']) && isset($record['id'])) {
                switch ($record['type']) {
                    case 'financial_entry':
                        FinancialEntry::where('id', $record['id'])->delete();
                        break;
                    case 'bank_movement':
                        BankMovement::where('id', $record['id'])->delete();
                        break;
                    case 'cash_withdrawal':
                        CashWithdrawal::where('id', $record['id'])->delete();
                        break;
                }
                return;
            }
        }
        
        // Fallback: búsqueda por características si no hay IDs guardados
        if ($this->destination_type === 'store') {
            if ($this->destination_fund === 'cash') {
                FinancialEntry::where('notes', 'like', '%"transfer_id":' . $this->id . '%')
                    ->where('notes', 'like', '%"direction":"incoming"%')
                    ->where('type', 'income')
                    ->delete();
            } elseif ($this->destination_fund === 'bank') {
                BankMovement::where('description', 'like', '%Transferencia desde%')
                    ->where('type', 'credit')
                    ->where('date', $this->date)
                    ->where('amount', $this->amount)
                    ->delete();
            }
        } elseif ($this->destination_type === 'wallet') {
            CashWithdrawal::where('cash_wallet_id', $this->destination_id)
                ->where('date', $this->date)
                ->where('amount', $this->amount)
                ->delete();
        }
    }

    /**
     * Validar que el origen tenga saldo suficiente
     * 
     * @param int|null $excludeBankMovementId ID del movimiento bancario a excluir del cálculo (para evitar contar dos veces)
     * @return array ['valid' => bool, 'message' => string|null]
     */
    protected function validateOriginBalance(?int $excludeBankMovementId = null): array
    {
        if ($this->origin_type === 'store') {
            if ($this->origin_fund === 'cash') {
                // Calcular saldo de efectivo de la tienda
                $cashBalance = $this->calculateStoreCashBalance($this->origin_id);
                if ($cashBalance < $this->amount) {
                    $store = Store::find($this->origin_id);
                    $storeName = $store ? $store->name : 'Tienda #' . $this->origin_id;
                    return [
                        'valid' => false,
                        'message' => "Saldo insuficiente en el origen. La tienda '{$storeName}' tiene un saldo de efectivo de " . number_format($cashBalance, 2, ',', '.') . " €, pero se requiere " . number_format($this->amount, 2, ',', '.') . " €."
                    ];
                }
            } elseif ($this->origin_fund === 'bank') {
                // Calcular saldo bancario de la tienda
                $bankBalance = $this->calculateStoreBankBalance($this->origin_id, $excludeBankMovementId);
                if ($bankBalance < $this->amount) {
                    $store = Store::find($this->origin_id);
                    $storeName = $store ? $store->name : 'Tienda #' . $this->origin_id;
                    return [
                        'valid' => false,
                        'message' => "Saldo insuficiente en el origen. La tienda '{$storeName}' tiene un saldo bancario de " . number_format($bankBalance, 2, ',', '.') . " €, pero se requiere " . number_format($this->amount, 2, ',', '.') . " €."
                    ];
                }
            }
        } elseif ($this->origin_type === 'wallet') {
            // Calcular saldo de la cartera
            $walletBalance = $this->calculateWalletBalance($this->origin_id);
            if ($walletBalance < $this->amount) {
                $wallet = CashWallet::find($this->origin_id);
                $walletName = $wallet ? $wallet->name : 'Cartera #' . $this->origin_id;
                return [
                    'valid' => false,
                    'message' => "Saldo insuficiente en el origen. La cartera '{$walletName}' tiene un saldo de " . number_format($walletBalance, 2, ',', '.') . " €, pero se requiere " . number_format($this->amount, 2, ',', '.') . " €."
                ];
            }
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Calcular el saldo de efectivo de una tienda
     */
    protected function calculateStoreCashBalance(int $storeId): float
    {
        // Sumar ingresos en efectivo
        $income = FinancialEntry::where('store_id', $storeId)
            ->where('type', 'income')
            ->where('expense_payment_method', 'cash')
            ->sum('income_amount') ?? 0;

        // Restar gastos en efectivo
        $expenses = FinancialEntry::where('store_id', $storeId)
            ->where('type', 'expense')
            ->where('expense_payment_method', 'cash')
            ->sum('expense_amount') ?? 0;

        // Sumar cierres diarios (cash_real - cash_expenses)
        $dailyCloses = FinancialEntry::where('store_id', $storeId)
            ->where('type', 'daily_close')
            ->get();
        
        $dailyCloseBalance = 0;
        foreach ($dailyCloses as $close) {
            $cashReal = (float)($close->cash_real ?? 0);
            $cashExpenses = (float)($close->cash_expenses ?? 0);
            $dailyCloseBalance += ($cashReal - $cashExpenses);
        }

        // Sumar transferencias entrantes en efectivo (excluir esta transferencia si es la actual)
        $incomingTransfersQuery = Transfer::where('destination_type', 'store')
            ->where('destination_id', $storeId)
            ->where('destination_fund', 'cash')
            ->where('status', 'reconciled');
        if ($this->id) {
            $incomingTransfersQuery->where('id', '!=', $this->id);
        }
        $incomingTransfers = $incomingTransfersQuery->sum('amount') ?? 0;

        // Restar transferencias salientes en efectivo (excluir esta transferencia si es la actual)
        $outgoingTransfersQuery = Transfer::where('origin_type', 'store')
            ->where('origin_id', $storeId)
            ->where('origin_fund', 'cash')
            ->where('status', 'reconciled');
        if ($this->id) {
            $outgoingTransfersQuery->where('id', '!=', $this->id);
        }
        $outgoingTransfers = $outgoingTransfersQuery->sum('amount') ?? 0;

        return $income - $expenses + $dailyCloseBalance + $incomingTransfers - $outgoingTransfers;
    }

    /**
     * Calcular el saldo bancario de una tienda
     * IMPORTANTE: Este método calcula el saldo REAL basándose en todos los movimientos,
     * igual que se hace en el apartado "Saldo Bancario". NO usa campos persistidos.
     * 
     * @param int $storeId ID de la tienda
     * @param int|null $excludeBankMovementId ID del movimiento bancario a excluir del cálculo
     * @return float Saldo bancario calculado
     */
    protected function calculateStoreBankBalance(int $storeId, ?int $excludeBankMovementId = null): float
    {
        $bankAccount = BankAccount::where('store_id', $storeId)->first();
        if (!$bankAccount) {
            return 0;
        }

        $balance = 0;
        
        // Determinar qué movimientos bancarios excluir del cálculo
        // Si se proporciona un excludeBankMovementId específico, usarlo
        // Si no, excluir todos los bank_movements asociados a este transfer
        $bankMovementIdsToExclude = [];
        if ($excludeBankMovementId) {
            $bankMovementIdsToExclude[] = $excludeBankMovementId;
        } else {
            // Excluir todos los bank_movements asociados a este transfer
            $bankMovementIdsToExclude = $this->bankMovements()->pluck('id')->toArray();
        }

        // 1. Sumar ingresos bancarios de cierres diarios (tpv)
        $tpvTotal = FinancialEntry::where('store_id', $storeId)
            ->where('type', 'daily_close')
            ->sum('tpv') ?? 0;
        $balance += $tpvTotal;

        // 2. Sumar transferencias de carteras (CashWalletTransfer) que ingresan al banco
        $walletTransfers = CashWalletTransfer::where('store_id', $storeId)
            ->sum('amount') ?? 0;
        $balance += $walletTransfers;

        // 3. Sumar movimientos bancarios de tipo credit (conciliados)
        $creditsQuery = BankMovement::where('bank_account_id', $bankAccount->id)
            ->where('type', 'credit')
            ->where('is_conciliated', true);
        
        if (!empty($bankMovementIdsToExclude)) {
            $creditsQuery->whereNotIn('id', $bankMovementIdsToExclude);
        }
        
        $credits = $creditsQuery->sum('amount') ?? 0;
        $balance += $credits;

        // 4. Restar movimientos bancarios de tipo debit (conciliados)
        $debitsQuery = BankMovement::where('bank_account_id', $bankAccount->id)
            ->where('type', 'debit')
            ->where('is_conciliated', true);
        
        if (!empty($bankMovementIdsToExclude)) {
            $debitsQuery->whereNotIn('id', $bankMovementIdsToExclude);
        }
        
        $debits = $debitsQuery->sum('amount') ?? 0;
        $balance -= $debits;

        // 5. Considerar movimientos transfer NO conciliados
        // Los movimientos transfer conciliados ya tienen sus efectos reflejados en los movimientos debit/credit
        // creados por Transfer::apply(), por lo que no debemos contarlos aquí
        $transfersQuery = BankMovement::where('bank_account_id', $bankAccount->id)
            ->where('type', 'transfer')
            ->where('is_conciliated', false);
        
        // Excluir los movimientos bancarios asociados a este transfer (para evitar contar dos veces)
        // Esto es crítico: cuando se valida el saldo antes de aplicar, los movimientos aún no están conciliados
        // pero debemos excluirlos del cálculo porque se van a aplicar
        if (!empty($bankMovementIdsToExclude)) {
            $transfersQuery->whereNotIn('id', $bankMovementIdsToExclude);
        }
        
        // Los movimientos transfer con importe positivo suman, con importe negativo restan
        $transfers = $transfersQuery->sum('amount') ?? 0;
        $balance += $transfers;

        // 6. Considerar traspasos recibidos (donde esta tienda es destino en BankMovement)
        // Estos son movimientos transfer con status 'conciliado' donde esta tienda es destino
        $receivedTransfersQuery = BankMovement::where('type', 'transfer')
            ->where('destination_store_id', $storeId)
            ->where('status', 'conciliado')
            ->where('is_conciliated', true);
        
        // Excluir los movimientos actuales si son traspasos recibidos
        if (!empty($bankMovementIdsToExclude)) {
            $receivedTransfersQuery->whereNotIn('id', $bankMovementIdsToExclude);
        }
        
        $receivedTransfers = $receivedTransfersQuery->sum('amount') ?? 0;
        $balance += $receivedTransfers;

        // 8. Considerar los movimientos creados por Transfer::apply() para transfers reconciliados
        // Estos son los movimientos debit/credit creados cuando se aplica un transfer reconciliado
        $appliedTransfers = 0;
        
        // Buscar transfers reconciliados que afectan esta cuenta bancaria
        $relatedTransfers = Transfer::where('status', 'reconciled')
            ->where(function($query) use ($storeId) {
                // Transfers donde esta tienda es origen (banco) - se resta
                $query->where(function($q) use ($storeId) {
                    $q->where('origin_type', 'store')
                      ->where('origin_id', $storeId)
                      ->where('origin_fund', 'bank');
                })
                // Transfers donde esta tienda es destino (banco) - se suma
                ->orWhere(function($q) use ($storeId) {
                    $q->where('destination_type', 'store')
                      ->where('destination_id', $storeId)
                      ->where('destination_fund', 'bank');
                });
            });
        
        // Excluir este transfer si existe (para evitar contar dos veces)
        if ($this->id) {
            $relatedTransfers->where('id', '!=', $this->id);
        }
        
        $relatedTransfersList = $relatedTransfers->get();
        
        foreach ($relatedTransfersList as $relatedTransfer) {
            if ($relatedTransfer->origin_type === 'store' && 
                $relatedTransfer->origin_id === $storeId && 
                $relatedTransfer->origin_fund === 'bank') {
                // Restar (dinero que sale de esta cuenta)
                $appliedTransfers -= $relatedTransfer->amount;
            }
            if ($relatedTransfer->destination_type === 'store' && 
                $relatedTransfer->destination_id === $storeId && 
                $relatedTransfer->destination_fund === 'bank') {
                // Sumar (dinero que entra a esta cuenta)
                $appliedTransfers += $relatedTransfer->amount;
            }
        }

        $balance += $appliedTransfers;

        return $balance;
    }

    /**
     * Calcular el saldo de una cartera
     */
    protected function calculateWalletBalance(int $walletId): float
    {
        // Sumar retiros (ingresos a la cartera)
        $withdrawals = CashWithdrawal::where('cash_wallet_id', $walletId)
            ->sum('amount') ?? 0;

        // Restar gastos
        $expenses = CashWalletExpense::where('cash_wallet_id', $walletId)
            ->sum('amount') ?? 0;

        // Sumar transferencias entrantes (excluir esta transferencia si es la actual)
        $incomingTransfersQuery = Transfer::where('destination_type', 'wallet')
            ->where('destination_id', $walletId)
            ->where('status', 'reconciled');
        if ($this->id) {
            $incomingTransfersQuery->where('id', '!=', $this->id);
        }
        $incomingTransfers = $incomingTransfersQuery->sum('amount') ?? 0;

        // Restar transferencias salientes (excluir esta transferencia si es la actual)
        $outgoingTransfersQuery = Transfer::where('origin_type', 'wallet')
            ->where('origin_id', $walletId)
            ->where('status', 'reconciled');
        if ($this->id) {
            $outgoingTransfersQuery->where('id', '!=', $this->id);
        }
        $outgoingTransfers = $outgoingTransfersQuery->sum('amount') ?? 0;

        return $withdrawals - $expenses + $incomingTransfers - $outgoingTransfers;
    }

    /**
     * Verificar si se necesita crear un efecto en el origen
     */
    protected function needsOriginEffect(): bool
    {
        // Store + cash: no crear financial_entry; el saldo se refleja en calculateStoreCashBalance() vía la tabla transfers.
        if ($this->origin_type === 'store' && $this->origin_fund === 'cash') {
            return false;
        }
        // bank_import: no crear BankMovement; se usan los movimientos importados enlazados (transfer_id).
        if ($this->method === 'bank_import' && $this->origin_type === 'store' && $this->origin_fund === 'bank') {
            return false;
        }
        // Excepción: si el destino es store + bank y el método es 'manual' y existe CashWalletTransfer,
        // no se necesita efecto en el origen (ya se resta en CashWalletTransfer)
        if ($this->origin_type === 'wallet' && 
            $this->destination_type === 'store' && 
            $this->destination_fund === 'bank' && 
            $this->method === 'manual') {
            $existingTransfer = \App\Models\CashWalletTransfer::where('cash_wallet_id', $this->origin_id)
                ->where('store_id', $this->destination_id)
                ->where('date', $this->date)
                ->where('amount', $this->amount)
                ->first();
            
            return $existingTransfer === null;
        }
        return true;
    }

    /**
     * Verificar si se necesita crear un efecto en el destino
     */
    protected function needsDestinationEffect(): bool
    {
        // Store + cash: no crear financial_entry (income); el saldo se refleja vía la tabla transfers.
        if ($this->destination_type === 'store' && $this->destination_fund === 'cash') {
            return false;
        }
        // bank_import: no crear BankMovement; se usan los movimientos importados enlazados (transfer_id).
        if ($this->method === 'bank_import' && $this->destination_type === 'store' && $this->destination_fund === 'bank') {
            return false;
        }
        return true;
    }

    /**
     * Obtener el nombre del origen
     */
    protected function getOriginName(): string
    {
        if ($this->origin_type === 'store') {
            $store = Store::find($this->origin_id);
            return $store ? $store->name : 'Tienda #' . $this->origin_id;
        } elseif ($this->origin_type === 'wallet') {
            $wallet = CashWallet::find($this->origin_id);
            return $wallet ? $wallet->name : 'Cartera #' . $this->origin_id;
        }
        return 'Origen desconocido';
    }

    /**
     * Obtener el nombre del destino
     */
    protected function getDestinationName(): string
    {
        if ($this->destination_type === 'store') {
            $store = Store::find($this->destination_id);
            return $store ? $store->name : 'Tienda #' . $this->destination_id;
        } elseif ($this->destination_type === 'wallet') {
            $wallet = CashWallet::find($this->destination_id);
            return $wallet ? $wallet->name : 'Cartera #' . $this->destination_id;
        }
        return 'Destino desconocido';
    }
}
