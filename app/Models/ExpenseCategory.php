<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
