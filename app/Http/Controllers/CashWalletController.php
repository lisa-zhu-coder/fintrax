<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\BankAccount;
use App\Models\CashWallet;
use App\Models\CashWalletExpense;
use App\Models\CashWalletTransfer;
use App\Models\CashWithdrawal;
use App\Models\FinancialEntry;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashWalletController extends Controller
{
    use EnforcesStoreScope;

    public function __construct()
    {
        $this->middleware('permission:treasury.cash_wallets.view')->only(['index', 'show']);
        $this->middleware('permission:treasury.cash_wallets.create')->only(['create', 'store', 'storeExpense', 'storeTransfer']);
        $this->middleware('permission:treasury.cash_wallets.edit')->only(['edit', 'update', 'editExpense', 'updateExpense', 'editTransfer', 'updateTransfer', 'editWithdrawal', 'updateWithdrawal']);
        $this->middleware('permission:treasury.cash_wallets.delete')->only(['destroy', 'destroyExpense', 'destroyTransfer', 'destroyWithdrawal']);
    }

    /**
     * Calcular el saldo de una cartera
     * Usa la misma lógica que el historial para garantizar consistencia
     * 
     * @param CashWallet $wallet
     * @return float
     */
    private function calculateBalance(CashWallet $wallet): float
    {
        // Obtener retiros de efectivo (entradas)
        $withdrawalsList = CashWithdrawal::where('cash_wallet_id', $wallet->id)->get();
        
        // Obtener gastos pagados desde la cartera (salidas) SOLO si existe el financial_entry asociado
        $expensesList = CashWalletExpense::where('cash_wallet_id', $wallet->id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('financial_entries')
                    ->whereColumn('financial_entries.id', 'cash_wallet_expenses.financial_entry_id');
            })
            ->with(['financialEntry'])
            ->get();
        
        // Obtener transferencias a banco (salidas)
        $transfersList = CashWalletTransfer::where('cash_wallet_id', $wallet->id)->get();
        
        // Calcular totales usando la misma lógica que el historial
        $totalWithdrawals = 0;
        foreach ($withdrawalsList as $withdrawal) {
            $totalWithdrawals += (float) $withdrawal->amount;
        }
        
        $totalExpenses = 0;
        foreach ($expensesList as $expense) {
            if ($expense->financialEntry) {
                $totalExpenses += (float) $expense->amount;
            }
        }
        
        $totalTransfers = 0;
        foreach ($transfersList as $transfer) {
            $totalTransfers += (float) $transfer->amount;
        }
        
        // Calcular saldo: retiros (entradas) - gastos (salidas) - transferencias (salidas)
        $balance = $totalWithdrawals - $totalExpenses - $totalTransfers;
        
        return round($balance, 2);
    }

    /**
     * Listar todas las carteras
     */
    public function index()
    {
        $cashWallets = CashWallet::orderBy('name')->get();
        $stores = $this->storesForCurrentUser();
        $suppliers = Supplier::orderBy('name')->get();

        // Calcular el saldo de cada cartera usando el método centralizado
        $walletsWithBalance = $cashWallets->map(function ($wallet) {
            $balance = $this->calculateBalance($wallet);
            
            return [
                'wallet' => $wallet,
                'balance' => $balance,
            ];
        });
        
        return view('cash-wallets.index', compact('walletsWithBalance', 'stores'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('cash-wallets.create');
    }

    /**
     * Guardar nueva cartera
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            CashWallet::create([
                'name' => $validated['name'],
            ]);

            return redirect()->route('cash-wallets.index')
                ->with('success', 'Cartera creada correctamente.');

        } catch (\Exception $e) {
            return redirect()->route('cash-wallets.index')
                ->with('error', 'Error al crear la cartera: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar historial de una cartera
     */
    public function show(CashWallet $cashWallet)
    {
        // Calcular saldo usando el método centralizado
        $finalBalance = $this->calculateBalance($cashWallet);
        
        // Obtener retiros de efectivo (entradas)
        $withdrawalsList = CashWithdrawal::where('cash_wallet_id', $cashWallet->id)
            ->with(['store'])
            ->get();
        
        // Obtener gastos pagados desde la cartera (salidas) SOLO si existe el financial_entry asociado
        $expensesList = CashWalletExpense::where('cash_wallet_id', $cashWallet->id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('financial_entries')
                    ->whereColumn('financial_entries.id', 'cash_wallet_expenses.financial_entry_id');
            })
            ->with(['financialEntry.store'])
            ->get();
        
        // Obtener transferencias a banco (salidas)
        $transfersList = CashWalletTransfer::where('cash_wallet_id', $cashWallet->id)
            ->with(['store'])
            ->get();
        
        // Unificar todos los movimientos en una sola colección
        $movements = collect();
        
        // Añadir retiros (entradas - positivos)
        foreach ($withdrawalsList as $withdrawal) {
            $movements->push([
                'date' => $withdrawal->date,
                'type' => 'withdrawal',
                'description' => 'Retiro de efectivo - ' . ($withdrawal->store->name ?? '—'),
                'amount' => (float) $withdrawal->amount,
                'id' => $withdrawal->id,
                'model_type' => 'CashWithdrawal',
            ]);
        }
        
        // Añadir gastos (salidas - negativos)
        // Ya están filtrados por whereExists, pero verificamos por seguridad
        foreach ($expensesList as $expense) {
            // Verificar que financialEntry existe y está cargado
            if (!$expense->financialEntry) {
                continue; // Saltar si no tiene financialEntry (no debería pasar con whereExists, pero por seguridad)
            }
            
            $entry = $expense->financialEntry;
            $concept = $entry->expense_concept ?? $entry->concept ?? 'Gasto';
            $store = $entry->store->name ?? '—';
            $category = $entry->expense_category ? ' (' . ucfirst(str_replace('_', ' ', $entry->expense_category)) . ')' : '';
            
            $movements->push([
                'date' => $entry->date,
                'type' => 'expense',
                'description' => $concept . ' - ' . $store . $category,
                'amount' => -(float) $expense->amount, // Negativo porque es salida
                'id' => $expense->id,
                'model_type' => 'CashWalletExpense',
                'financial_entry_id' => $entry->id,
            ]);
        }
        
        // Añadir transferencias (salidas - negativos)
        foreach ($transfersList as $transfer) {
            $movements->push([
                'date' => $transfer->date,
                'type' => 'transfer',
                'description' => 'Ingreso en banco - ' . ($transfer->store->name ?? '—'),
                'amount' => -(float) $transfer->amount, // Negativo porque es salida
                'id' => $transfer->id,
                'model_type' => 'CashWalletTransfer',
            ]);
        }
        
        // Ordenar por fecha ascendente
        $movements = $movements->sortBy(function ($movement) {
            return $movement['date']->format('Y-m-d');
        })->values();
        
        // Calcular saldo acumulado línea a línea
        $runningBalance = 0;
        $movementsWithBalance = $movements->map(function ($movement) use (&$runningBalance) {
            $runningBalance += $movement['amount'];
            $movement['balance'] = round($runningBalance, 2);
            return $movement;
        });
        
        // Calcular el saldo final desde los movimientos (para verificar)
        $calculatedFromMovements = $movementsWithBalance->isNotEmpty() 
            ? $movementsWithBalance->last()['balance'] 
            : 0;
        
        // Usar el saldo calculado desde movimientos si coincide, sino usar el método centralizado
        // Esto ayuda a identificar discrepancias
        if (abs($calculatedFromMovements - $finalBalance) > 0.01) {
            Log::warning('Discrepancia en cálculo de saldo', [
                'wallet_id' => $cashWallet->id,
                'calculated_from_movements' => $calculatedFromMovements,
                'final_balance_from_method' => $finalBalance,
                'difference' => abs($calculatedFromMovements - $finalBalance),
            ]);
            // Usar el cálculo desde movimientos como referencia
            $finalBalance = $calculatedFromMovements;
        }
        
        // Obtener traspasos relacionados (Transfer) donde la cartera es origen o destino
        $relatedTransfers = Transfer::where(function($query) use ($cashWallet) {
                $query->where(function($q) use ($cashWallet) {
                    $q->where('origin_type', 'wallet')
                      ->where('origin_id', $cashWallet->id);
                })->orWhere(function($q) use ($cashWallet) {
                    $q->where('destination_type', 'wallet')
                      ->where('destination_id', $cashWallet->id);
                });
            })
            ->with(['origin', 'destination', 'creator', 'bankMovements'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('cash-wallets.show', compact('cashWallet', 'movementsWithBalance', 'finalBalance', 'relatedTransfers'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(CashWallet $cashWallet)
    {
        return view('cash-wallets.edit', compact('cashWallet'));
    }

    /**
     * Actualizar cartera
     */
    public function update(Request $request, CashWallet $cashWallet)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $cashWallet->update([
                'name' => $validated['name'],
            ]);

            return redirect()->route('cash-wallets.index')
                ->with('success', 'Cartera actualizada correctamente.');

        } catch (\Exception $e) {
            return redirect()->route('cash-wallets.index')
                ->with('error', 'Error al actualizar la cartera: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar cartera
     */
    public function destroy(CashWallet $cashWallet)
    {
        try {
            // Verificar si la cartera tiene movimientos (retiros de efectivo)
            $hasWithdrawals = CashWithdrawal::where('cash_wallet_id', $cashWallet->id)->exists();

            if ($hasWithdrawals) {
                return redirect()->route('cash-wallets.index')
                    ->with('error', 'No se puede eliminar la cartera porque tiene movimientos asociados.');
            }

            $cashWallet->delete();

            return redirect()->route('cash-wallets.index')
                ->with('success', 'Cartera eliminada correctamente.');

        } catch (\Exception $e) {
            return redirect()->route('cash-wallets.index')
                ->with('error', 'Error al eliminar la cartera: ' . $e->getMessage());
        }
    }

    /**
     * Registrar gasto desde cartera
     */
    public function storeExpense(Request $request, CashWallet $cashWallet)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'expense_category' => 'nullable|string|max:255',
            'concept' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            // Crear registro financiero (supplier_id debe ser null cuando está vacío, no "")
            $financialEntry = FinancialEntry::create([
                'date' => $validated['date'],
                'store_id' => $validated['store_id'],
                'supplier_id' => !empty($validated['supplier_id']) ? $validated['supplier_id'] : null,
                'type' => 'expense',
                'expense_payment_method' => 'cash',
                'expense_amount' => $validated['amount'],
                'amount' => $validated['amount'],
                'total_amount' => $validated['amount'],
                'status' => 'pagado',
                'paid_amount' => $validated['amount'],
                'expense_category' => $validated['expense_category'] ?? null,
                'expense_source' => 'cartera',
                'expense_concept' => $validated['concept'] ?? null,
                'concept' => $validated['concept'] ?? null,
                'notes' => json_encode([
                    'source' => 'cash_wallet',
                    'cash_wallet_id' => $cashWallet->id,
                ]),
                'created_by' => Auth::id(),
            ]);

            // Crear registro en cash_wallet_expenses
            CashWalletExpense::create([
                'cash_wallet_id' => $cashWallet->id,
                'financial_entry_id' => $financialEntry->id,
                'amount' => $validated['amount'],
            ]);

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Gasto registrado correctamente desde la cartera.');

        } catch (\Exception $e) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'Error al registrar el gasto: ' . $e->getMessage());
        }
    }

    /**
     * Registrar transferencia (ingreso en banco) desde cartera
     */
    public function storeTransfer(Request $request, CashWallet $cashWallet)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            // Calcular saldo actual usando el método centralizado
            $currentBalance = $this->calculateBalance($cashWallet);
            
            // Verificar que la cartera tiene saldo suficiente
            if ($currentBalance < $validated['amount']) {
                return redirect()->route('cash-wallets.show', $cashWallet)
                    ->with('error', 'La cartera no tiene saldo suficiente. Saldo disponible: ' . number_format($currentBalance, 2, ',', '.') . ' €');
            }

            // Verificar que la tienda de destino tiene una cuenta bancaria configurada
            $bankAccount = BankAccount::where('store_id', $validated['store_id'])->first();
            if (!$bankAccount) {
                $store = Store::find($validated['store_id']);
                $storeName = $store ? $store->name : 'Tienda #' . $validated['store_id'];
                return redirect()->route('cash-wallets.show', $cashWallet)
                    ->with('error', "La tienda \"{$storeName}\" no tiene una cuenta bancaria configurada. Configura la cuenta en la ficha de la tienda (Datos de la empresa / Tiendas) antes de ingresar efectivo en banco.");
            }

            // Crear registro en cash_wallet_transfers (para el historial y resta de saldo de cartera)
            $cashWalletTransfer = CashWalletTransfer::create([
                'cash_wallet_id' => $cashWallet->id,
                'store_id' => $validated['store_id'],
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'created_by' => Auth::id(),
            ]);

            // Crear registro Transfer para gestionar el efecto en el banco de la tienda
            // El Transfer::apply() creará automáticamente:
            // - BankMovement tipo credit (suma al banco de la tienda)
            // NOTA: NO crea CashWalletExpense porque ya tenemos CashWalletTransfer que resta de la cartera
            $transfer = Transfer::create([
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'origin_type' => 'wallet',
                'origin_id' => $cashWallet->id,
                'origin_fund' => 'cash',
                'destination_type' => 'store',
                'destination_id' => $validated['store_id'],
                'destination_fund' => 'bank',
                'method' => 'manual',
                'status' => 'pending', // Crear como pending inicialmente
                'notes' => 'Ingreso de efectivo desde cartera ' . $cashWallet->name,
                'created_by' => Auth::id(),
            ]);

            // Actualizar el status a 'reconciled' ANTES de llamar a apply()
            $transfer->update(['status' => 'reconciled']);
            $transfer->refresh();

            // Aplicar la transferencia (suma al banco de la tienda)
            // La resta de la cartera ya se hizo con CashWalletTransfer
            $result = $transfer->apply();
            if (!$result['success']) {
                // Si falla la aplicación, eliminar los registros creados
                $transfer->update(['status' => 'pending']);
                $cashWalletTransfer->delete();
                $transfer->delete();
                return redirect()->route('cash-wallets.show', $cashWallet)
                    ->with('error', $result['message'] ?? 'Error al aplicar la transferencia. Verifica los datos.');
            }

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Ingreso de efectivo registrado y aplicado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error al registrar transferencia desde cartera', [
                'cash_wallet_id' => $cashWallet->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'Error al registrar la transferencia: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición de retiro
     */
    public function editWithdrawal(CashWallet $cashWallet, CashWithdrawal $withdrawal)
    {
        if ($withdrawal->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'El retiro no pertenece a esta cartera.');
        }

        $withdrawal->load('store');
        $stores = $this->getAvailableStores();
        
        return view('cash-wallets.withdrawals.edit', compact('cashWallet', 'withdrawal', 'stores'));
    }

    /**
     * Actualizar retiro
     */
    public function updateWithdrawal(Request $request, CashWallet $cashWallet, CashWithdrawal $withdrawal)
    {
        if ($withdrawal->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'El retiro no pertenece a esta cartera.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $withdrawal->update([
                'date' => $validated['date'],
                'store_id' => $validated['store_id'],
                'amount' => round($validated['amount'], 2),
            ]);

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Retiro actualizado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar el retiro: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar retiro
     */
    public function destroyWithdrawal(Request $request, CashWallet $cashWallet, CashWithdrawal $withdrawal)
    {
        if ($withdrawal->cash_wallet_id !== $cashWallet->id) {
            $redirectTo = $request->input('redirect_to');
            if ($redirectTo) {
                return redirect($redirectTo)->with('error', 'El retiro no pertenece a esta cartera.');
            }
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'El retiro no pertenece a esta cartera.');
        }

        try {
            $withdrawal->delete();

            $redirectTo = $request->input('redirect_to');
            if ($redirectTo) {
                return redirect($redirectTo)
                    ->with('success', 'Recogida de efectivo eliminada correctamente. El saldo de la cartera se ha ajustado.');
            }

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Retiro eliminado correctamente. El saldo de la cartera se ha ajustado.');
        } catch (\Exception $e) {
            $redirectTo = $request->input('redirect_to');
            if ($redirectTo) {
                return redirect($redirectTo)
                    ->with('error', 'Error al eliminar el retiro: ' . $e->getMessage());
            }
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'Error al eliminar el retiro: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición de gasto
     */
    public function editExpense(CashWallet $cashWallet, CashWalletExpense $expense)
    {
        if ($expense->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'El gasto no pertenece a esta cartera.');
        }

        $expense->load('financialEntry.store');
        
        if (!$expense->financialEntry) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'El gasto no tiene un registro financiero asociado.');
        }
        
        $stores = $this->getAvailableStores();
        $suppliers = Supplier::orderBy('name')->get();

        return view('cash-wallets.expenses.edit', compact('cashWallet', 'expense', 'stores', 'suppliers'));
    }

    /**
     * Actualizar gasto
     */
    public function updateExpense(Request $request, CashWallet $cashWallet, CashWalletExpense $expense)
    {
        if ($expense->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'El gasto no pertenece a esta cartera.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'concept' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            // Actualizar el gasto de la cartera
            $expense->update([
                'amount' => round($validated['amount'], 2),
            ]);

            // Actualizar el registro financiero asociado
            if ($expense->financialEntry) {
                $expense->financialEntry->update([
                    'date' => $validated['date'],
                    'store_id' => $validated['store_id'],
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'expense_amount' => round($validated['amount'], 2),
                    'amount' => round($validated['amount'], 2),
                    'total_amount' => round($validated['amount'], 2),
                    'expense_concept' => $validated['concept'] ?? null,
                    'concept' => $validated['concept'] ?? null,
                ]);
            }

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Gasto actualizado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar el gasto: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar gasto
     */
    public function destroyExpense(CashWallet $cashWallet, CashWalletExpense $expense)
    {
        if ($expense->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'El gasto no pertenece a esta cartera.');
        }

        try {
            // Eliminar el registro financiero asociado si existe
            if ($expense->financialEntry) {
                $expense->financialEntry->delete();
            }

            // Eliminar el gasto de la cartera
            $expense->delete();

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Gasto eliminado correctamente. El registro financiero asociado también ha sido eliminado.');
        } catch (\Exception $e) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'Error al eliminar el gasto: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición de transferencia
     */
    public function editTransfer(CashWallet $cashWallet, CashWalletTransfer $transfer)
    {
        if ($transfer->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'La transferencia no pertenece a esta cartera.');
        }

        $transfer->load('store');
        $stores = $this->getAvailableStores();
        
        return view('cash-wallets.transfers.edit', compact('cashWallet', 'transfer', 'stores'));
    }

    /**
     * Actualizar transferencia
     */
    public function updateTransfer(Request $request, CashWallet $cashWallet, CashWalletTransfer $transfer)
    {
        if ($transfer->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'La transferencia no pertenece a esta cartera.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            // Verificar saldo suficiente con el nuevo importe
            $currentBalance = $this->calculateBalance($cashWallet);
            $oldAmount = (float) $transfer->amount;
            $newAmount = (float) $validated['amount'];
            
            // Calcular el saldo después de revertir el importe anterior y aplicar el nuevo
            $balanceAfterChange = $currentBalance + $oldAmount - $newAmount;
            
            if ($balanceAfterChange < 0) {
                return back()->withInput()->with('error', 
                    'La cartera no tendría saldo suficiente con este importe. Saldo disponible después del cambio: ' . 
                    number_format($balanceAfterChange, 2, ',', '.') . ' €');
            }

            $transfer->update([
                'date' => $validated['date'],
                'store_id' => $validated['store_id'],
                'amount' => round($validated['amount'], 2),
            ]);

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Transferencia actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar la transferencia: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar transferencia (ingreso a banco) desde el historial de la cartera.
     * También elimina el Transfer y el BankMovement asociados para mantener consistencia.
     */
    public function destroyTransfer(CashWallet $cashWallet, CashWalletTransfer $transfer)
    {
        if ($transfer->cash_wallet_id !== $cashWallet->id) {
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'La transferencia no pertenece a esta cartera.');
        }

        try {
            DB::beginTransaction();

            // Buscar el Transfer asociado (cartera → banco tienda, mismo día/importe/tienda)
            $relatedTransfer = Transfer::where('origin_type', 'wallet')
                ->where('origin_id', $cashWallet->id)
                ->where('destination_type', 'store')
                ->where('destination_id', $transfer->store_id)
                ->where('destination_fund', 'bank')
                ->where('method', 'manual')
                ->whereDate('date', $transfer->date)
                ->where('amount', $transfer->amount)
                ->first();

            if ($relatedTransfer) {
                if ($relatedTransfer->status === 'reconciled') {
                    $rollbackResult = $relatedTransfer->rollback();
                    if (!$rollbackResult['success']) {
                        DB::rollBack();
                        return redirect()->route('cash-wallets.show', $cashWallet)
                            ->with('error', $rollbackResult['message'] ?? 'Error al revertir el traspaso asociado.');
                    }
                    $relatedTransfer->update(['status' => 'pending']);
                }
                $relatedTransfer->delete();
            }

            $transfer->delete();
            DB::commit();

            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('success', 'Transferencia eliminada correctamente. El saldo de la cartera y el movimiento en banco (si existía) se han ajustado.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar transferencia de cartera', [
                'cash_wallet_transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('cash-wallets.show', $cashWallet)
                ->with('error', 'Error al eliminar la transferencia: ' . $e->getMessage());
        }
    }
}
