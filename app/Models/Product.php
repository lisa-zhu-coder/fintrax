<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_category_id',
        'name',
        'is_sellable',
        'is_ingredient',
        'is_composed',
        'base_unit',
        'stock_unit',
        'consumption_unit',
        'conversion_factor',
    ];

    protected $casts = [
        'is_sellable' => 'boolean',
        'is_ingredient' => 'boolean',
        'is_composed' => 'boolean',
        'conversion_factor' => 'decimal:4',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /** Ingredientes de este producto (cuando es compuesto) */
    public function ingredients(): HasMany
    {
        return $this->hasMany(ProductIngredient::class);
    }

    /** Productos donde este es ingrediente */
    public function ingredientOf(): HasMany
    {
        return $this->hasMany(ProductIngredient::class, 'ingredient_product_id');
    }

    public function inventoryLines(): HasMany
    {
        return $this->hasMany(InventoryLine::class);
    }

    public function weeklySales(): HasMany
    {
        return $this->hasMany(WeeklySale::class);
    }

    /** Productos con línea de inventario: ingrediente o vendible no compuesto */
    public function hasInventory(): bool
    {
        return $this->is_ingredient || ($this->is_sellable && !$this->is_composed);
    }

    public function isComposite(): bool
    {
        return $this->is_composed;
    }

    public function isIngredient(): bool
    {
        return $this->is_ingredient;
    }

    /** Se puede vender directamente */
    public function isSellable(): bool
    {
        return $this->is_sellable;
    }

    /** Productos que aparecen en el formulario de ventas: vendible o compuesto */
    public function isSellableForSales(): bool
    {
        return $this->is_sellable || $this->is_composed;
    }
}
