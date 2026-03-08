<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyObjectiveSetting extends Model
{
    protected $fillable = [
        'store_id',
        'year',
        'month',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function settingValues(): HasMany
    {
        return $this->hasMany(MonthlyObjectiveSettingValue::class, 'monthly_objective_setting_id');
    }

    /**
     * Objetivos definidos para la empresa de la tienda (o empresa actual).
     */
    public static function getObjectiveDefinitionsForStore(?int $storeId): \Illuminate\Support\Collection
    {
        $companyId = null;
        if ($storeId !== null) {
            $store = Store::find($storeId);
            $companyId = $store?->company_id;
        }
        if ($companyId === null) {
            $companyId = session('company_id');
        }
        if ($companyId === null) {
            return collect([]);
        }
        return ObjectiveDefinition::where('company_id', $companyId)->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * Obtener porcentajes para una tienda, año y mes.
     * Devuelve array de porcentajes en el mismo orden que los objective definitions de la empresa.
     */
    public static function getPercentagesForStoreMonth(?int $storeId, string $month, ?int $year = null): array
    {
        $month = str_pad((string) (int) $month, 2, '0', STR_PAD_LEFT);
        $year = $year ?? (int) date('Y');
        $definitions = self::getObjectiveDefinitionsForStore($storeId);
        if ($definitions->isEmpty()) {
            return [];
        }
        $specific = null;
        $generic = null;
        if ($storeId !== null) {
            $specific = self::with('settingValues.objectiveDefinition')
                ->where('store_id', $storeId)->where('year', $year)->where('month', $month)->first();
        }
        $generic = self::with('settingValues.objectiveDefinition')
            ->whereNull('store_id')->where('year', $year)->where('month', $month)->first();
        $row = $specific ?? $generic;
        if (! $row) {
            return $definitions->map(fn () => 0.0)->all();
        }
        $byObjId = $row->settingValues->keyBy('objective_definition_id');
        return $definitions->map(fn ($def) => (float) ($byObjId->get($def->id)?->percentage ?? 0))->all();
    }
}
