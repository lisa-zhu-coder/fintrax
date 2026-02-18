<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Http\Controllers\Concerns\SyncsStoresFromBusinesses;
use App\Models\CashWallet;
use App\Models\CashWalletTransfer;
use App\Models\Store;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    use EnforcesStoreScope;
    use SyncsStoresFromBusinesses;

    public function __construct()
    {
        $this->middleware('permission:treasury.transfers.view')->only(['index', 'create']);
        $this->middleware('permission:treasury.transfers.create')->only(['store']);
        $this->middleware('permission:treasury.transfers.edit')->only(['edit', 'update']);
        $this->middleware('permission:treasury.transfers.delete')->only(['destroy']);
    }

    /**
     * Listar todos los traspasos
     */
    public function index(Request $request)
    {
        $this->syncStoresFromBusinesses();

        $query = Transfer::with(['origin', 'destination', 'creator']);

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            $query->where(function ($q) use ($enforcedStoreId) {
                $q->where(function ($q2) use ($enforcedStoreId) {
                    $q2->where('origin_type', 'store')->where('origin_id', $enforcedStoreId);
                })->orWhere(function ($q2) use ($enforcedStoreId) {
                    $q2->where('destination_type', 'store')->where('destination_id', $enforcedStoreId);
                });
            });
        }

        // Filtros
        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('method') && $request->method !== 'all') {
            $query->where('method', $request->method);
        }

        $transfers = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('financial.transfers.index', compact('transfers'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $this->syncStoresFromBusinesses();

        $stores = $this->getAvailableStores();
        $wallets = CashWallet::all();

        return view('financial.transfers.create', compact('stores', 'wallets'));
    }

    /**
     * Guardar nuevo traspaso
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'origin_type' => 'required|in:store,wallet',
            'origin_id' => 'required',
            'origin_fund' => 'required|in:cash,bank',
            'destination_type' => 'required|in:store,wallet',
            'destination_id' => 'required',
            'destination_fund' => 'required|in:cash,bank',
            'status' => 'required|in:pending,reconciled',
            'notes' => 'nullable|string',
        ], [
            'origin_id.required' => 'Debes seleccionar un origen.',
            'destination_id.required' => 'Debes seleccionar un destino.',
        ]);

        // Validar que origen ≠ destino (incluyendo el fondo)
        if ($validated['origin_type'] === $validated['destination_type'] && 
            $validated['origin_id'] == $validated['destination_id'] &&
            $validated['origin_fund'] === $validated['destination_fund']) {
            return back()->withInput()->with('error', 'El origen y el destino no pueden ser iguales.');
        }

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            if ($validated['origin_type'] === 'store' && (int) $validated['origin_id'] !== (int) $enforcedStoreId) {
                return back()->withInput()->with('error', 'Solo puedes usar tu tienda como origen.');
            }
            if ($validated['destination_type'] === 'store' && (int) $validated['destination_id'] !== (int) $enforcedStoreId) {
                return back()->withInput()->with('error', 'Solo puedes usar tu tienda como destino.');
            }
        }

        // Validar casos permitidos según las reglas del usuario
        $originFund = $validated['origin_fund'];
        $destinationFund = $validated['destination_fund'];
        $originType = $validated['origin_type'];
        $destinationType = $validated['destination_type'];

        // Casos permitidos:
        // 1) Banco → Banco (tienda a tienda)
        // 2) Efectivo → Banco (tienda o cartera a tienda)
        // 3) Banco → Efectivo
        // 4) Efectivo → Efectivo (tienda ↔ tienda, cartera ↔ tienda, cartera ↔ cartera)

        $isValid = false;
        $errorMessage = '';

        if ($originFund === 'bank' && $destinationFund === 'bank') {
            // Caso 1: Banco → Banco (solo tienda a tienda)
            if ($originType === 'store' && $destinationType === 'store') {
                $isValid = true;
            } else {
                $errorMessage = 'Las transferencias Banco → Banco solo están permitidas entre tiendas.';
            }
        } elseif ($originFund === 'cash' && $destinationFund === 'bank') {
            // Caso 2: Efectivo → Banco (tienda o cartera a tienda)
            if ($destinationType === 'store') {
                $isValid = true;
            } else {
                $errorMessage = 'Las transferencias Efectivo → Banco solo están permitidas hacia tiendas.';
            }
        } elseif ($originFund === 'bank' && $destinationFund === 'cash') {
            // Caso 3: Banco → Efectivo
            if ($originType === 'store') {
                $isValid = true;
            } else {
                $errorMessage = 'Las transferencias Banco → Efectivo solo están permitidas desde tiendas.';
            }
        } elseif ($originFund === 'cash' && $destinationFund === 'cash') {
            // Caso 4: Efectivo → Efectivo (cualquier combinación permitida)
            $isValid = true;
        } else {
            $errorMessage = 'Combinación de fondos no permitida.';
        }

        if (!$isValid) {
            return back()->withInput()->with('error', $errorMessage);
        }

        // Crear el traspaso con status 'pending' inicialmente
        $transfer = Transfer::create([
            'date' => $validated['date'],
            'amount' => $validated['amount'],
            'origin_type' => $validated['origin_type'],
            'origin_id' => $validated['origin_id'],
            'origin_fund' => $validated['origin_fund'],
            'destination_type' => $validated['destination_type'],
            'destination_id' => $validated['destination_id'],
            'destination_fund' => $validated['destination_fund'],
            'method' => 'manual',
            'status' => 'pending', // Siempre crear como 'pending' inicialmente
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Si el estado solicitado es reconciled, aplicar la transferencia
        // IMPORTANTE: Primero actualizar el status, luego aplicar
        if ($validated['status'] === 'reconciled') {
            // Actualizar el status a 'reconciled' ANTES de llamar a apply()
            $transfer->update(['status' => 'reconciled']);
            $transfer->refresh(); // Asegurar que el modelo tenga el status actualizado
            
            // Ahora aplicar la transferencia
            $result = $transfer->apply();
            if (!$result['success']) {
                // Si falla, revertir el status y eliminar el transfer
                $transfer->update(['status' => 'pending']);
                $transfer->delete();
                return back()->withInput()->with('error', $result['message'] ?? 'Error al aplicar la transferencia. Verifica los datos.');
            }
            return redirect()->route('transfers.index')->with('success', 'Traspaso creado y aplicado correctamente.');
        }

        return redirect()->route('transfers.index')->with('success', 'Traspaso creado correctamente.');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Transfer $transfer)
    {
        $this->authorizeTransferAccess($transfer);
        $this->syncStoresFromBusinesses();

        $stores = $this->storesForCurrentUser();
        $wallets = CashWallet::all();

        return view('financial.transfers.edit', compact('transfer', 'stores', 'wallets'));
    }

    /**
     * Actualizar traspaso
     */
    public function update(Request $request, Transfer $transfer)
    {
        $this->authorizeTransferAccess($transfer);
        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'origin_type' => 'required|in:store,wallet',
            'origin_id' => 'required',
            'origin_fund' => 'required|in:cash,bank',
            'destination_type' => 'required|in:store,wallet',
            'destination_id' => 'required',
            'destination_fund' => 'required|in:cash,bank',
            'method' => 'required|in:manual,bank_import',
            'status' => 'required|in:pending,reconciled',
            'notes' => 'nullable|string',
        ]);

        // Validar que origen ≠ destino
        if ($validated['origin_type'] === $validated['destination_type'] && 
            $validated['origin_id'] == $validated['destination_id'] &&
            $validated['origin_fund'] === $validated['destination_fund']) {
            return back()->withInput()->with('error', 'El origen y el destino no pueden ser iguales.');
        }

        $enforcedStoreId = auth()->user()->getEnforcedStoreId();
        if ($enforcedStoreId !== null) {
            if ($validated['origin_type'] === 'store' && (int) $validated['origin_id'] !== (int) $enforcedStoreId) {
                return back()->withInput()->with('error', 'Solo puedes usar tu tienda como origen.');
            }
            if ($validated['destination_type'] === 'store' && (int) $validated['destination_id'] !== (int) $enforcedStoreId) {
                return back()->withInput()->with('error', 'Solo puedes usar tu tienda como destino.');
            }
        }

        // Detectar si hay cambios en campos que afectan los saldos
        $hasBalanceChanges = 
            $transfer->amount != $validated['amount'] ||
            $transfer->origin_type != $validated['origin_type'] ||
            $transfer->origin_id != $validated['origin_id'] ||
            $transfer->origin_fund != $validated['origin_fund'] ||
            $transfer->destination_type != $validated['destination_type'] ||
            $transfer->destination_id != $validated['destination_id'] ||
            $transfer->destination_fund != $validated['destination_fund'];

        $wasReconciled = $transfer->status === 'reconciled';
        $willBeReconciled = $validated['status'] === 'reconciled';
        $statusChanged = $wasReconciled !== $willBeReconciled;

        // Si estaba reconciliado, hacer rollback primero (antes de actualizar los datos)
        if ($wasReconciled && ($hasBalanceChanges || !$willBeReconciled)) {
            $rollbackResult = $transfer->rollback();
            if (!$rollbackResult['success']) {
                return back()->withInput()->with('error', $rollbackResult['message'] ?? 'Error al revertir la transferencia anterior.');
            }
            // Actualizar el status a 'pending' después del rollback
            $transfer->update(['status' => 'pending']);
            $transfer->refresh();
        }

        // Actualizar los campos (excepto status si necesita procesamiento especial)
        $updateData = $validated;
        if ($willBeReconciled && !$wasReconciled) {
            // Si va a cambiar de pending a reconciled, primero actualizar sin el status
            unset($updateData['status']);
            $transfer->update($updateData);
            // Luego actualizar el status a 'reconciled'
            $transfer->update(['status' => 'reconciled']);
        } else {
            // Actualizar todo normalmente
            $transfer->update($updateData);
        }
        $transfer->refresh();

        // Si el status cambió de pending a reconciled, aplicar la transferencia
        // IMPORTANTE: Solo aplicar si el status cambió de != reconciled a reconciled
        if ($willBeReconciled && !$wasReconciled) {
            $result = $transfer->apply();
            if (!$result['success']) {
                // Si falla, revertir el status a 'pending'
                $transfer->update(['status' => 'pending']);
                return back()->withInput()->with('error', $result['message'] ?? 'Error al aplicar la transferencia actualizada. Verifica los datos.');
            }
            return redirect()->route('transfers.index')->with('success', 'Traspaso actualizado y aplicado correctamente.');
        } elseif ($willBeReconciled && $wasReconciled && $hasBalanceChanges) {
            // Si estaba reconciliado, se hizo rollback, y ahora debe estar reconciliado de nuevo
            // Aplicar con los nuevos datos
            $result = $transfer->apply();
            if (!$result['success']) {
                // Si falla, revertir el status a 'pending'
                $transfer->update(['status' => 'pending']);
                return back()->withInput()->with('error', $result['message'] ?? 'Error al aplicar la transferencia actualizada. Verifica los datos.');
            }
            return redirect()->route('transfers.index')->with('success', 'Traspaso actualizado y aplicado correctamente.');
        }

        return redirect()->route('transfers.index')->with('success', 'Traspaso actualizado correctamente.');
    }

    /**
     * Eliminar traspaso.
     * Si es un ingreso a banco desde cartera (wallet→store bank manual), también se elimina
     * el registro del historial de la cartera (CashWalletTransfer) para mantener consistencia.
     */
    public function destroy(Transfer $transfer)
    {
        // Si está reconciliado, ejecutar rollback() para restaurar los saldos (ej. eliminar BankMovement creado)
        if ($transfer->status === 'reconciled') {
            $rollbackResult = $transfer->rollback();
            if (!$rollbackResult['success']) {
                return back()->with('error', $rollbackResult['message'] ?? 'Error al revertir la transferencia antes de eliminarla. Los saldos no se han restaurado.');
            }
            $transfer->update(['status' => 'pending']);
        }

        // Si es cartera → banco de tienda (ingreso a banco), eliminar también el registro del historial de la cartera
        if ($transfer->origin_type === 'wallet' && $transfer->destination_type === 'store' && $transfer->destination_fund === 'bank' && $transfer->method === 'manual') {
            $cashWalletTransfer = CashWalletTransfer::where('cash_wallet_id', $transfer->origin_id)
                ->where('store_id', $transfer->destination_id)
                ->whereDate('date', $transfer->date)
                ->where('amount', $transfer->amount)
                ->first();
            if ($cashWalletTransfer) {
                $cashWalletTransfer->delete();
            }
        }

        $transfer->delete();

        return redirect()->route('transfers.index')->with('success', 'Traspaso eliminado y saldos restaurados correctamente.');
    }

    /**
     * Comprueba que el usuario pueda acceder al traspaso (origen o destino sea su tienda).
     */
    protected function authorizeTransferAccess(Transfer $transfer): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return;
        }
        $storeId = $user->getEnforcedStoreId();
        if ($storeId === null) {
            return;
        }
        $originOk = $transfer->origin_type === 'store' && (int) $transfer->origin_id === (int) $storeId;
        $destOk = $transfer->destination_type === 'store' && (int) $transfer->destination_id === (int) $storeId;
        if (!$originOk && !$destOk) {
            abort(403, 'No tienes acceso a este traspaso.');
        }
    }
}
