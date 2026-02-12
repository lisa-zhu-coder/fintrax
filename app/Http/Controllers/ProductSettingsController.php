<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSettingsController extends Controller
{
    public const UNITS = ['unidades', 'gr', 'kg', 'ml', 'cl', 'litros'];

    public function __construct()
    {
        $this->middleware('permission:settings.products.view')->only(['index', 'show']);
        $this->middleware('permission:settings.products.create')->only(['create', 'store', 'storeCategory']);
        $this->middleware('permission:settings.products.edit')->only(['edit', 'update']);
        $this->middleware('permission:settings.products.delete')->only(['destroy']);
    }

    public function index()
    {
        $categories = ProductCategory::with(['products' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();
        return view('settings.products.index', compact('categories'));
    }

    public function create()
    {
        $categories = ProductCategory::orderBy('name')->get();
        $ingredientProducts = Product::where('is_ingredient', true)->orderBy('name')->get();
        return view('settings.products.create', compact('categories', 'ingredientProducts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_category_id' => 'required|exists:product_categories,id',
            'is_sellable' => 'sometimes|boolean',
            'is_ingredient' => 'sometimes|boolean',
            'is_composed' => 'sometimes|boolean',
            'base_unit' => 'nullable|string|in:' . implode(',', self::UNITS),
            'stock_unit' => 'nullable|string|in:' . implode(',', self::UNITS),
            'consumption_unit' => 'nullable|string|in:' . implode(',', self::UNITS),
            'conversion_factor' => 'nullable|numeric|min:0',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_product_id' => 'required_with:ingredients|exists:products,id',
            'ingredients.*.quantity_per_unit' => 'required_with:ingredients|numeric|min:0',
            'ingredients.*.unit' => 'required_with:ingredients|string|in:' . implode(',', self::UNITS),
        ]);

        $isSellable = $request->boolean('is_sellable');
        $isIngredient = $request->boolean('is_ingredient');
        $isComposed = $request->boolean('is_composed');

        if (!$isSellable && !$isIngredient && !$isComposed) {
            return redirect()->back()->withInput()->withErrors(['is_sellable' => 'Debe activar al menos un rol: venta directa, ingrediente o compuesto.']);
        }
        if ($isComposed && empty($validated['ingredients'])) {
            return redirect()->back()->withInput()->withErrors(['ingredients' => 'Un producto compuesto debe tener al menos un ingrediente.']);
        }
        if ($isIngredient && (empty($validated['stock_unit']) || empty($validated['consumption_unit']) || empty($validated['conversion_factor']))) {
            return redirect()->back()->withInput()->withErrors(['stock_unit' => 'Un ingrediente debe definir unidad de stock, unidad de consumo y factor de conversión.']);
        }

        $product = DB::transaction(function () use ($validated, $isSellable, $isIngredient, $isComposed) {
            $product = Product::create([
                'name' => $validated['name'],
                'product_category_id' => $validated['product_category_id'],
                'is_sellable' => $isSellable,
                'is_ingredient' => $isIngredient,
                'is_composed' => $isComposed,
                'base_unit' => $validated['base_unit'] ?? 'unidades',
                'stock_unit' => $isIngredient ? ($validated['stock_unit'] ?? null) : null,
                'consumption_unit' => $isIngredient ? ($validated['consumption_unit'] ?? null) : null,
                'conversion_factor' => $isIngredient ? ($validated['conversion_factor'] ?? null) : null,
            ]);

            if ($isComposed && !empty($validated['ingredients'])) {
                foreach ($validated['ingredients'] as $ing) {
                    if (!empty($ing['ingredient_product_id']) && isset($ing['quantity_per_unit']) && $ing['quantity_per_unit'] > 0) {
                        ProductIngredient::create([
                            'product_id' => $product->id,
                            'ingredient_product_id' => $ing['ingredient_product_id'],
                            'quantity_per_unit' => $ing['quantity_per_unit'],
                            'unit' => $ing['unit'] ?? 'gr',
                        ]);
                    }
                }
            }

            return $product;
        });

        return redirect()->route('product-settings.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::orderBy('name')->get();
        $ingredientProducts = Product::where('is_ingredient', true)->where('id', '!=', $product->id)->orderBy('name')->get();
        $product->load('ingredients.ingredientProduct');
        return view('settings.products.edit', compact('product', 'categories', 'ingredientProducts'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_category_id' => 'required|exists:product_categories,id',
            'is_sellable' => 'sometimes|boolean',
            'is_ingredient' => 'sometimes|boolean',
            'is_composed' => 'sometimes|boolean',
            'base_unit' => 'nullable|string|in:' . implode(',', self::UNITS),
            'stock_unit' => 'nullable|string|in:' . implode(',', self::UNITS),
            'consumption_unit' => 'nullable|string|in:' . implode(',', self::UNITS),
            'conversion_factor' => 'nullable|numeric|min:0',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_product_id' => 'required_with:ingredients|exists:products,id',
            'ingredients.*.quantity_per_unit' => 'required_with:ingredients|numeric|min:0',
            'ingredients.*.unit' => 'required_with:ingredients|string|in:' . implode(',', self::UNITS),
        ]);

        $isSellable = $request->boolean('is_sellable');
        $isIngredient = $request->boolean('is_ingredient');
        $isComposed = $request->boolean('is_composed');

        if (!$isSellable && !$isIngredient && !$isComposed) {
            return redirect()->back()->withInput()->withErrors(['is_sellable' => 'Debe activar al menos un rol: venta directa, ingrediente o compuesto.']);
        }
        if ($isComposed && empty($validated['ingredients'])) {
            return redirect()->back()->withInput()->withErrors(['ingredients' => 'Un producto compuesto debe tener al menos un ingrediente.']);
        }
        if ($isIngredient && (empty($validated['stock_unit']) || empty($validated['consumption_unit']) || empty($validated['conversion_factor']))) {
            return redirect()->back()->withInput()->withErrors(['stock_unit' => 'Un ingrediente debe definir unidad de stock, unidad de consumo y factor de conversión.']);
        }

        DB::transaction(function () use ($product, $validated, $isSellable, $isIngredient, $isComposed) {
            $product->update([
                'name' => $validated['name'],
                'product_category_id' => $validated['product_category_id'],
                'is_sellable' => $isSellable,
                'is_ingredient' => $isIngredient,
                'is_composed' => $isComposed,
                'base_unit' => $validated['base_unit'] ?? 'unidades',
                'stock_unit' => $isIngredient ? ($validated['stock_unit'] ?? null) : null,
                'consumption_unit' => $isIngredient ? ($validated['consumption_unit'] ?? null) : null,
                'conversion_factor' => $isIngredient ? ($validated['conversion_factor'] ?? null) : null,
            ]);

            $product->ingredients()->delete();
            if ($isComposed && !empty($validated['ingredients'])) {
                foreach ($validated['ingredients'] as $ing) {
                    if (!empty($ing['ingredient_product_id']) && isset($ing['quantity_per_unit']) && $ing['quantity_per_unit'] > 0) {
                        ProductIngredient::create([
                            'product_id' => $product->id,
                            'ingredient_product_id' => $ing['ingredient_product_id'],
                            'quantity_per_unit' => $ing['quantity_per_unit'],
                            'unit' => $ing['unit'] ?? 'gr',
                        ]);
                    }
                }
            }
        });

        return redirect()->route('product-settings.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('product-settings.index')->with('success', 'Producto eliminado correctamente.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        ProductCategory::create($validated);
        return redirect()->route('product-settings.index')->with('success', 'Categoría creada correctamente.');
    }
}
