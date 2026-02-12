<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyBusiness extends Model
{
    use SoftDeletes, BelongsToCompany;
    
    protected $table = 'company_businesses';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'street',
        'postal_code',
        'city',
        'email',
    ];
}
