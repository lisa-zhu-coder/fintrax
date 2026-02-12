<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_category_id',
        'name',
        'year',
        'month',
        'week_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'week_number' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryLine::class)->orderBy('id');
    }
}
