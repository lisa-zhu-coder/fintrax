<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanType;
use App\Services\LoanCalculationService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:loans.main.view')->only(['index', 'show']);
        $this->middleware('permission:loans.main.create')->only(['create', 'store']);
        $this->middleware('permission:loans.main.edit')->only(['edit', 'update']);
        $this->middleware('permission:loans.main.delete')->only(['destroy']);
    }

    public function index()
    {
        $loans = Loan::with(['loanType', 'creator'])->orderBy('name')->get();
        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        $loanTypes = LoanType::orderBy('name')->get();
        $interestIndexOptions = $this->interestIndexOptions();
        return view('loans.create', compact('loanTypes', 'interestIndexOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'loan_type_id' => 'required|exists:loan_types,id',
            'direction' => 'required|in:company_owes,company_lends',
            'principal_amount' => 'required|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'interest_type' => 'nullable|in:fixed,variable',
            'interest_index' => 'nullable|string|max:50',
            'interest_spread' => 'nullable|numeric|min:0',
            'interest_floor' => 'nullable|numeric|min:0',
            'interest_cap' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'opening_fee' => 'nullable|numeric|min:0',
            'settlement_frequency' => 'nullable|string|in:monthly',
            'amortization_system' => 'nullable|string|in:french',
            'initial_index_rate' => 'nullable|numeric|min:0',
        ]);
        $validated['created_by'] = auth()->id();
        $validated = $this->normalizeLoanOptionals($validated);
        unset($validated['initial_index_rate']);

        $loan = Loan::create($validated);

        if ($loan->term_months && $loan->start_date) {
            $variableRate = $request->filled('initial_index_rate') ? (float) $request->initial_index_rate : null;
            app(LoanCalculationService::class)->generateInstallments($loan, $variableRate);
        }

        return redirect()->route('loans.index')->with('success', 'Préstamo creado correctamente.');
    }

    public function show(Loan $loan)
    {
        $loan->load([
            'loanType',
            'payments' => fn ($q) => $q->orderBy('date', 'desc')->orderBy('id', 'desc'),
            'installments',
            'creator',
        ]);
        return view('loans.show', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $loanTypes = LoanType::orderBy('name')->get();
        $interestIndexOptions = $this->interestIndexOptions();
        return view('loans.edit', compact('loan', 'loanTypes', 'interestIndexOptions'));
    }

    public function update(Request $request, Loan $loan)
    {
        $this->ensureCompany($loan);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'loan_type_id' => 'required|exists:loan_types,id',
            'direction' => 'required|in:company_owes,company_lends',
            'principal_amount' => 'required|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'interest_type' => 'nullable|in:fixed,variable',
            'interest_index' => 'nullable|string|max:50',
            'interest_spread' => 'nullable|numeric|min:0',
            'interest_floor' => 'nullable|numeric|min:0',
            'interest_cap' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'opening_fee' => 'nullable|numeric|min:0',
            'settlement_frequency' => 'nullable|string|in:monthly',
            'amortization_system' => 'nullable|string|in:french',
            'initial_index_rate' => 'nullable|numeric|min:0',
        ]);
        $validated = $this->normalizeLoanOptionals($validated);
        unset($validated['initial_index_rate']);

        $loan->update($validated);

        if ($loan->term_months && $loan->start_date) {
            $variableRate = $request->filled('initial_index_rate') ? (float) $request->initial_index_rate : null;
            app(LoanCalculationService::class)->generateInstallments($loan, $variableRate);
        }

        return redirect()->route('loans.show', $loan)->with('success', 'Préstamo actualizado correctamente.');
    }

    public function destroy(Loan $loan)
    {
        $this->ensureCompany($loan);
        $loan->delete();
        return redirect()->route('loans.index')->with('success', 'Préstamo eliminado correctamente.');
    }

    private function ensureCompany(Loan $loan): void
    {
        $companyId = session('company_id');
        if ($companyId === null || (int) $loan->company_id !== (int) $companyId) {
            abort(403, 'No puedes modificar préstamos de otra empresa.');
        }
    }

    private function interestIndexOptions(): array
    {
        return [
            'euribor_1m' => 'Euribor 1 mes',
            'euribor_3m' => 'Euribor 3 meses',
            'euribor_6m' => 'Euribor 6 meses',
            'euribor_12m' => 'Euribor 12 meses',
        ];
    }

    /**
     * Convierte cadenas vacías a null en campos opcionales para evitar error de cast decimal/integer.
     */
    private function normalizeLoanOptionals(array $data): array
    {
        $optionals = [
            'interest_rate', 'opening_fee', 'term_months', 'start_date', 'interest_type',
            'interest_index', 'interest_spread', 'interest_floor', 'interest_cap',
            'settlement_frequency', 'amortization_system',
        ];
        foreach ($optionals as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }
        return $data;
    }
}
