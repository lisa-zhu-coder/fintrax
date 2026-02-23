<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategorySettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.expense_categories.view')->only(['index']);
        $this->middleware('permission:settings.expense_categories.create')->only(['store']);
        $this->middleware('permission:settings.expense_categories.edit')->only(['update']);
        $this->middleware('permission:settings.expense_categories.delete')->only(['destroy']);
    }

    /**
     * Listado de categorías de gastos de la empresa actual (session company_id).
     */
    public function index()
    {
        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        return view('settings.expense-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $maxOrder = ExpenseCategory::max('sort_order') ?? 0;
        ExpenseCategory::create([
            'name' => $validated['name'],
            'sort_order' => $maxOrder + 1,
        ]);
        return redirect()->route('expense-categories-settings.index')
            ->with('success', 'Categoría de gasto creada correctamente.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $this->ensureCompany($expenseCategory);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $expenseCategory->update($validated);
        return redirect()->route('expense-categories-settings.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $this->ensureCompany($expenseCategory);
        $expenseCategory->delete();
        return redirect()->route('expense-categories-settings.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }

    private function ensureCompany(ExpenseCategory $expenseCategory): void
    {
        $companyId = session('company_id');
        if ($companyId === null || (int) $expenseCategory->company_id !== (int) $companyId) {
            abort(403, 'No puedes modificar categorías de otra empresa.');
        }
    }
}
