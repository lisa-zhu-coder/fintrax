<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyObjectiveSettingValue extends Model
{
    protected $fillable = [
        'monthly_objective_setting_id',
        'objective_definition_id',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function monthlyObjectiveSetting(): BelongsTo
    {
        return $this->belongsTo(MonthlyObjectiveSetting::class);
    }

    public function objectiveDefinition(): BelongsTo
    {
        return $this->belongsTo(ObjectiveDefinition::class);
    }
}
