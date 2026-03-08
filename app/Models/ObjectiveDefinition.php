<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjectiveDefinition extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function settingValues(): HasMany
    {
        return $this->hasMany(MonthlyObjectiveSettingValue::class, 'objective_definition_id');
    }
}
