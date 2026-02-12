<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLine;
use App\Models\InventoryLinePurchaseRecord;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryInventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.products.view')->only(['categories', 'inventories', 'show']);
        $this->middleware('permission:inventory.products.create')->only(['create', 'store', 'addPurchase']);
        $this->middleware('permission:inventory.products.edit')->only(['update']);
    }

    public function categories(Request $request)
    {
        $year = (int) $request->get('year');
        $month = (int) $request->get('month');
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        if (!$year || !$month) {
            return view('inventory.categories.index', [
                'categories' => collect(),
                'year' => $year ?: now()->year,
                'month' => $month ?: now()->month,
                'monthNames' => $monthNames,
                'showCategories' => false,
            ]);
        }

        $categories = ProductCategory::orderBy('name')->get()->map(function ($cat) use ($year, $month) {
            $cat->inventories_count = $cat->inventories()
                ->where('year', $year)
                ->where('month', $month)
                ->whereNotNull('week_number')
                ->count();
            return $cat;
        });

        return view('inventory.categories.index', compact('categories', 'year', 'month', 'monthNames') + ['showCategories' => true]);
    }

    public function inventories(Request $request, ProductCategory $category)
    {
        $year = (int) $request->get('year');
        $month = (int) $request->get('month');
        if (!$year || !$month) {
            return redirect()->route('inventory.categories.index')
                ->with('error', 'Debes seleccionar año y mes.');
        }

        $inventories = $category->inventories()
            ->where('year', $year)
            ->where('month', $month)
            ->whereNotNull('week_number')
            ->orderBy('week_number')
            ->get();

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return view('inventory.categories.inventories', compact('category', 'inventories', 'monthNames', 'year', 'month'));
    }

    public function create(Request $request, ProductCategory $category)
    {
        $year = (int) $request->get('year');
        $month = (int) $request->get('month');
        if (!$year || !$month) {
            return redirect()->route('inventory.categories.index')
                ->with('error', 'Debes seleccionar año y mes.');
        }

        $sourceInventories = $category->inventories()
            ->whereNotNull('week_number')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('week_number')
            ->get();

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return view('inventory.categories.create', compact('category', 'monthNames', 'year', 'month', 'sourceInventories'));
    }

    public function store(Request $request, ProductCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'week_number' => 'required|integer|in:1,2,3,4,5',
            'source_inventory_id' => 'nullable|exists:inventories,id',
        ]);

        $existing = Inventory::where('product_category_id', $category->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('week_number', $validated['week_number'])
            ->exists();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Ya existe un inventario para esa categoría, mes y semana.');
        }

        if (!empty($validated['source_inventory_id'])) {
            $source = Inventory::find($validated['source_inventory_id']);
            if (!$source || $source->product_category_id !== $category->id) {
                return redirect()->back()->withInput()->with('error', 'El inventario de procedencia no pertenece a esta categoría.');
            }
        }

        $companyId = session('company_id');
        $inv = Inventory::create([
            'company_id' => $companyId,
            'product_category_id' => $category->id,
            'name' => $validated['name'],
            'year' => $validated['year'],
            'month' => $validated['month'],
            'week_number' => $validated['week_number'],
        ]);

        $products = Product::where('product_category_id', $category->id)
            ->where(function ($q) {
                $q->where('is_ingredient', true)
                    ->orWhere(function ($q2) {
                        $q2->where('is_sellable', true)->where('is_composed', false);
                    });
            })
            ->orderBy('name')
            ->get();

        $sourceLinesByProduct = collect();
        if (!empty($validated['source_inventory_id'])) {
            $sourceLinesByProduct = InventoryLine::where('inventory_id', $validated['source_inventory_id'])
                ->get()
                ->keyBy('product_id');
        }

        foreach ($products as $product) {
            $initialQty = 0;
            if ($sourceLinesByProduct->has($product->id)) {
                $initialQty = $sourceLinesByProduct->get($product->id)->real_quantity ?? 0;
            }
            InventoryLine::create([
                'inventory_id' => $inv->id,
                'product_id' => $product->id,
                'initial_quantity' => $initialQty,
            ]);
        }

        return redirect()->route('inventory.categories.show', [$category, $inv])
            ->with('success', 'Inventario creado correctamente.');
    }

    public function show(ProductCategory $category, Inventory $inventory)
    {
        if ($inventory->product_category_id !== $category->id) {
            abort(404);
        }

        $lines = $inventory->lines()->with(['product', 'purchaseRecords.user'])->get();
        $totals = [
            'expected' => $lines->sum(fn ($l) => $l->expected_quantity),
            'real' => $lines->sum('real_quantity'),
            'discrepancy' => $lines->sum(fn ($l) => $l->discrepancy),
        ];

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return view('inventory.categories.show', compact('category', 'inventory', 'lines', 'totals', 'monthNames'));
    }

    public function update(Request $request, ProductCategory $category, Inventory $inventory)
    {
        if ($inventory->product_category_id !== $category->id) {
            abort(404);
        }

        $validated = $request->validate([
            'lines' => 'required|array',
            'lines.*.initial_quantity' => 'nullable|integer|min:0',
            'lines.*.real_quantity' => 'required|integer|min:0',
        ]);

        foreach ($validated['lines'] as $lineId => $data) {
            InventoryLine::where('inventory_id', $inventory->id)
                ->where('id', $lineId)
                ->update([
                    'initial_quantity' => (int) ($data['initial_quantity'] ?? 0),
                    'real_quantity' => (int) ($data['real_quantity'] ?? 0),
                ]);
        }

        return redirect()->route('inventory.categories.show', [$category, $inventory])
            ->with('success', 'Inventario actualizado.');
    }

    public function addPurchase(Request $request, ProductCategory $category, Inventory $inventory)
    {
        if ($inventory->product_category_id !== $category->id) {
            abort(404);
        }

        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'lines' => 'required|array',
            'lines.*.quantity' => 'required|integer|min:0',
        ]);

        $userId = auth()->id();
        $added = 0;

        DB::transaction(function () use ($validated, $inventory, $userId, &$added) {
            foreach ($validated['lines'] as $lineId => $data) {
                $quantity = (int) ($data['quantity'] ?? 0);
                if ($quantity < 1) continue;

                $line = InventoryLine::where('inventory_id', $inventory->id)->find($lineId);
                if (!$line) continue;

                InventoryLinePurchaseRecord::create([
                    'inventory_line_id' => $line->id,
                    'quantity' => $quantity,
                    'purchase_date' => $validated['purchase_date'],
                    'user_id' => $userId,
                ]);
                $line->increment('acquired_quantity', $quantity);
                $added++;
            }
        });

        $msg = $added > 0 ? "Compra registrada. Se han añadido cantidades a {$added} producto(s)." : 'No se añadió ninguna cantidad.';
        return redirect()->route('inventory.categories.show', [$category, $inventory])
            ->with($added > 0 ? 'success' : 'error', $msg);
    }
}
