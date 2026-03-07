<?php

namespace App\Http\Controllers;

use App\Models\LoanType;
use Illuminate\Http\Request;

class LoanTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.loan_types.manage')->only(['index', 'store', 'update', 'destroy']);
    }

    /**
     * Listado de tipos de préstamo de la empresa actual.
     */
    public function index()
    {
        $loanTypes = LoanType::orderBy('name')->get();
        return view('settings.loan-types.index', compact('loanTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:financial,commercial',
            'has_interest' => 'boolean',
            'has_opening_fee' => 'boolean',
        ]);
        $validated['has_interest'] = $request->boolean('has_interest');
        $validated['has_opening_fee'] = $request->boolean('has_opening_fee');
        LoanType::create($validated);
        return redirect()->route('loan-types-settings.index')
            ->with('success', 'Tipo de préstamo creado correctamente.');
    }

    public function update(Request $request, LoanType $loanType)
    {
        $this->ensureCompany($loanType);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:financial,commercial',
            'has_interest' => 'boolean',
            'has_opening_fee' => 'boolean',
        ]);
        $validated['has_interest'] = $request->boolean('has_interest');
        $validated['has_opening_fee'] = $request->boolean('has_opening_fee');
        $loanType->update($validated);
        return redirect()->route('loan-types-settings.index')
            ->with('success', 'Tipo de préstamo actualizado correctamente.');
    }

    public function destroy(LoanType $loanType)
    {
        $this->ensureCompany($loanType);
        if ($loanType->isInUse()) {
            return redirect()->route('loan-types-settings.index')
                ->with('error', 'No se puede eliminar este tipo porque está en uso por uno o más préstamos.');
        }
        $loanType->delete();
        return redirect()->route('loan-types-settings.index')
            ->with('success', 'Tipo de préstamo eliminado correctamente.');
    }

    private function ensureCompany(LoanType $loanType): void
    {
        $companyId = session('company_id');
        if ($companyId === null || (int) $loanType->company_id !== (int) $companyId) {
            abort(403, 'No puedes modificar tipos de préstamo de otra empresa.');
        }
    }
}
