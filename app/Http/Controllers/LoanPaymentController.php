<?php

namespace App\Http\Controllers;

use App\Models\BankMovement;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:loans.payments.create')->only(['store', 'conciliateFromBank']);
        $this->middleware('permission:loans.payments.edit')->only(['edit', 'update']);
        $this->middleware('permission:loans.payments.delete')->only(['destroy']);
    }

    /**
     * Conciliar un movimiento bancario como pago de préstamo (desde conciliación bancaria).
     */
    public function conciliateFromBank(Request $request, BankMovement $bankMovement)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:500',
        ]);
        $loan = Loan::findOrFail($validated['loan_id']);
        $companyId = session('company_id');
        if ($companyId === null || (int) $loan->company_id !== (int) $companyId) {
            abort(403, 'No puedes asignar pagos a préstamos de otra empresa.');
        }
        $loanPayment = LoanPayment::create([
            'loan_id' => $loan->id,
            'date' => $bankMovement->date,
            'amount' => (float) $validated['amount'],
            'source' => 'bank_reconciliation',
            'bank_movement_id' => $bankMovement->id,
            'comment' => $validated['comment'] ?? null,
            'created_by' => auth()->id(),
        ]);
        $bankMovement->update([
            'is_conciliated' => true,
            'status' => 'conciliado',
        ]);
        return redirect()->route('financial.bank-conciliation')
            ->with('success', 'Movimiento conciliado como pago del préstamo "' . $loan->name . '".');
    }

    public function store(Request $request, Loan $loan)
    {
        $this->ensureCompany($loan);
        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:500',
        ]);
        $validated['loan_id'] = $loan->id;
        $validated['source'] = 'manual';
        $validated['created_by'] = auth()->id();
        LoanPayment::create($validated);
        return redirect()->route('loans.show', $loan)->with('success', 'Pago registrado correctamente.');
    }

    public function edit(Loan $loan, LoanPayment $payment)
    {
        $this->ensureCompany($loan);
        if ((int) $payment->loan_id !== (int) $loan->id) {
            abort(404);
        }
        if ($payment->source === 'bank_reconciliation') {
            return redirect()->route('loans.show', $loan)->with('error', 'Los pagos por conciliación bancaria no se pueden editar aquí.');
        }
        return view('loans.payments.edit', compact('loan', 'payment'));
    }

    public function update(Request $request, Loan $loan, LoanPayment $payment)
    {
        $this->ensureCompany($loan);
        if ((int) $payment->loan_id !== (int) $loan->id) {
            abort(404);
        }
        if ($payment->source === 'bank_reconciliation') {
            return redirect()->route('loans.show', $loan)->with('error', 'Los pagos por conciliación bancaria no se pueden editar.');
        }
        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'comment' => 'nullable|string|max:500',
        ]);
        $payment->update($validated);
        return redirect()->route('loans.show', $loan)->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Loan $loan, LoanPayment $payment)
    {
        $this->ensureCompany($loan);
        if ((int) $payment->loan_id !== (int) $loan->id) {
            abort(404);
        }
        $payment->delete();
        return redirect()->route('loans.show', $loan)->with('success', 'Pago eliminado correctamente.');
    }

    private function ensureCompany(Loan $loan): void
    {
        $companyId = session('company_id');
        if ($companyId === null || (int) $loan->company_id !== (int) $companyId) {
            abort(403, 'No puedes modificar pagos de préstamos de otra empresa.');
        }
    }
}
