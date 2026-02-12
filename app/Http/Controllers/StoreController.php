<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Store;
use App\Models\Transfer;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Show the form for editing the specified store.
     */
    public function edit(Store $store)
    {
        // Cargar la tienda con sus cuentas bancarias
        $store->load('bankAccounts');
        
        // Obtener traspasos relacionados (Transfer) donde la tienda es origen o destino
        $relatedTransfers = Transfer::where(function($query) use ($store) {
                $query->where(function($q) use ($store) {
                    $q->where('origin_type', 'store')
                      ->where('origin_id', $store->id);
                })->orWhere(function($q) use ($store) {
                    $q->where('destination_type', 'store')
                      ->where('destination_id', $store->id);
                });
            })
            ->with(['origin', 'destination', 'creator', 'bankMovements'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Pasar las cuentas bancarias y transfers relacionados a la vista de edición
        return view('stores.edit', compact('store', 'relatedTransfers'));
    }

    /**
     * Store a newly created bank account for the store.
     */
    public function storeBankAccount(Store $store, Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'iban' => 'required|string|max:255|unique:bank_accounts,iban',
        ]);

        try {
            BankAccount::create([
                'store_id' => $store->id,
                'bank_name' => $validated['bank_name'],
                'iban' => $validated['iban'],
            ]);
            
            return redirect()->route('stores.edit', $store)
                ->with('success', 'Cuenta bancaria creada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('stores.edit', $store)
                ->with('error', 'Error al crear la cuenta bancaria: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified bank account.
     */
    public function destroyBankAccount(BankAccount $bankAccount)
    {
        try {
            $storeId = $bankAccount->store_id;
            
            // Verificar si la cuenta tiene movimientos bancarios asociados
            if ($bankAccount->bankMovements()->exists()) {
                return redirect()->route('stores.edit', $storeId)
                    ->with('error', 'No se puede eliminar la cuenta bancaria porque tiene movimientos asociados.');
            }
            
            $bankAccount->delete();
            
            return redirect()->route('stores.edit', $storeId)
                ->with('success', 'Cuenta bancaria eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('stores.edit', $bankAccount->store_id)
                ->with('error', 'Error al eliminar la cuenta bancaria: ' . $e->getMessage());
        }
    }
}
