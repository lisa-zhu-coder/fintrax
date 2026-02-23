<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->hasPermission('admin.suppliers.view') || auth()->user()->hasPermission('orders.main.view')) {
                return $next($request);
            }
            abort(403);
        })->only(['index', 'show']);
        $this->middleware(function ($request, $next) {
            if (auth()->user()->hasPermission('admin.suppliers.create') || auth()->user()->hasPermission('orders.main.create')) {
                return $next($request);
            }
            abort(403);
        })->only(['create', 'store']);
        $this->middleware('permission:admin.suppliers.edit')->only(['edit', 'update']);
        $this->middleware('permission:admin.suppliers.delete')->only('destroy');
    }

    public function index()
    {
        $suppliers = Supplier::with('expenseCategory')->withCount('orders')->orderBy('name')->get();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $supplier = new Supplier();
        $expenseCategories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        return view('suppliers.create', compact('supplier', 'expenseCategories'));
    }

    public function store(Request $request)
    {
        $companyId = session('company_id');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'id')->where('company_id', $companyId)],
            'cif' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['orders' => fn ($q) => $q->with('store')->orderBy('date', 'desc')]);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $expenseCategories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();
        return view('suppliers.edit', compact('supplier', 'expenseCategories'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $companyId = session('company_id');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'id')->where('company_id', $companyId)],
            'cif' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->orders()->exists()) {
            return redirect()->route('suppliers.index')
                ->with('error', 'No se puede eliminar el proveedor porque tiene pedidos asociados.');
        }
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
