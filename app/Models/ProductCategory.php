<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_category_id');
    }
}
