<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesMonth;
use App\Models\SalesWeek;
use App\Models\WeeklySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventorySalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sales.products.view')->only(['index']);
        $this->middleware('permission:sales.products.create')->only(['store']);
        $this->middleware('permission:sales.products.edit')->only(['store']);
    }

    public function index(Request $request)
    {
        $year = (int) ($request->get('year') ?? now()->year);
        $month = (int) ($request->get('month') ?? now()->month);

        $companyId = session('company_id');
        $salesMonth = SalesMonth::firstOrCreate(
            ['company_id' => $companyId, 'year' => $year, 'month' => $month],
            ['company_id' => $companyId, 'year' => $year, 'month' => $month]
        );

        $sellableProducts = Product::where(function ($q) {
            $q->where('is_sellable', true)->orWhere('is_composed', true);
        })
            ->orderBy('name')
            ->get();

        $weeks = $salesMonth->weeks;
        $weekNumbers = $weeks->pluck('week_number')->unique()->sort()->values();

        $salesByProductWeek = [];
        foreach ($weeks as $week) {
            foreach ($week->weeklySales as $ws) {
                $salesByProductWeek[$ws->product_id][$week->week_number] = $ws->quantity_sold;
            }
        }

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $categories = ProductCategory::orderBy('name')->get();
        $inventories = collect();

        return view('inventory.sales.index', compact(
            'year', 'month', 'sellableProducts', 'weekNumbers', 'salesByProductWeek',
            'monthNames', 'categories', 'inventories'
        ));
    }

    public function getInventories(Request $request)
    {
        $categoryId = $request->get('category_id');
        $year = (int) $request->get('year');
        $month = (int) $request->get('month');
        $weekNumber = (int) $request->get('week_number');

        if (!$categoryId || !$year || !$month) {
            return response()->json([]);
        }

        $companyId = session('company_id');
        $query = Inventory::where('company_id', $companyId)
            ->where('product_category_id', $categoryId)
            ->where('year', $year)
            ->where('month', $month);

        if ($weekNumber > 0) {
            $query->where('week_number', $weekNumber);
        } else {
            $query->whereNull('week_number');
        }

        $inventories = $query->orderBy('name')->get(['id', 'name', 'week_number']);

        return response()->json($inventories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'week_number' => 'required|integer|in:1,2,3,4',
            'category_id' => 'required|exists:product_categories,id',
            'inventory_id' => 'required|exists:inventories,id',
            'sales' => 'required|array',
            'sales.*.quantity_sold' => 'required|integer|min:0',
        ]);

        $companyId = session('company_id');
        $inventory = Inventory::findOrFail($validated['inventory_id']);
        if ($inventory->product_category_id != $validated['category_id']) {
            return redirect()->back()->with('error', 'El inventario no pertenece a la categoría seleccionada.');
        }

        $salesMonth = SalesMonth::firstOrCreate(
            ['company_id' => $companyId, 'year' => $validated['year'], 'month' => $validated['month']],
            ['company_id' => $companyId, 'year' => $validated['year'], 'month' => $validated['month']]
        );
        $salesWeek = SalesWeek::firstOrCreate(
            ['sales_month_id' => $salesMonth->id, 'week_number' => $validated['week_number']],
            ['sales_month_id' => $salesMonth->id, 'week_number' => $validated['week_number']]
        );

        DB::transaction(function () use ($salesWeek, $validated, $inventory) {
            foreach ($validated['sales'] as $productId => $data) {
                $newQty = (int) ($data['quantity_sold'] ?? 0);
                $existing = WeeklySale::where('sales_week_id', $salesWeek->id)->where('product_id', $productId)->first();
                $oldQty = $existing ? $existing->quantity_sold : 0;
                $delta = $newQty - $oldQty;

                WeeklySale::updateOrCreate(
                    ['sales_week_id' => $salesWeek->id, 'product_id' => $productId],
                    [
                        'quantity_sold' => $newQty,
                        'inventory_id' => $inventory->id,
                    ]
                );

                if ($delta !== 0) {
                    $this->applySalesToInventory((int) $productId, $delta, $inventory);
                }
            }
        });

        return redirect()->route('inventory.sales.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', 'Ventas guardadas correctamente.');
    }

    protected function applySalesToInventory(int $productId, int $delta, Inventory $inventory): void
    {
        $product = Product::with('ingredients.ingredientProduct')->find($productId);
        if (!$product) return;

        if ($product->isComposite()) {
            foreach ($product->ingredients as $ing) {
                $ingProduct = $ing->ingredientProduct;
                $usedDelta = $delta * (float) $ing->quantity_per_unit;
                if ($ingProduct->conversion_factor && $ingProduct->stock_unit !== $ing->unit) {
                    $usedDelta = $usedDelta / (float) $ingProduct->conversion_factor;
                }
                $line = InventoryLine::firstOrCreate(
                    [
                        'inventory_id' => $inventory->id,
                        'product_id' => $ingProduct->id,
                    ],
                    ['inventory_id' => $inventory->id, 'product_id' => $ingProduct->id]
                );
                $line->increment('used_quantity', (int) round($usedDelta));
            }
        } else {
            $line = InventoryLine::firstOrCreate(
                [
                    'inventory_id' => $inventory->id,
                    'product_id' => $productId,
                ],
                ['inventory_id' => $inventory->id, 'product_id' => $productId]
            );
            $line->increment('sold_quantity', $delta);
        }
    }
}
