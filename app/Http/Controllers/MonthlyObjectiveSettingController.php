<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\MonthlyObjectiveSetting;
use App\Models\MonthlyObjectiveSettingValue;
use App\Models\ObjectiveDefinition;
use App\Models\Store;
use Illuminate\Http\Request;

class MonthlyObjectiveSettingController extends Controller
{
    use EnforcesStoreScope;

    public function __construct()
    {
        $this->middleware('permission:settings.objectives.view')->only(['index']);
        $this->middleware('permission:settings.objectives.create')->only(['store', 'storeObjectiveDefinition']);
        $this->middleware('permission:settings.objectives.delete')->only(['destroy', 'destroyObjectiveDefinition']);
    }

    /**
     * Objetivos definidos para la empresa actual.
     */
    private function getObjectiveDefinitions(): \Illuminate\Support\Collection
    {
        $companyId = $this->currentCompanyId();
        if (! $companyId) {
            return collect([]);
        }
        return ObjectiveDefinition::where('company_id', $companyId)->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * Muestra Objetivos de ventas: gestionar objetivos (añadir/eliminar) y porcentajes por mes.
     */
    public function index(Request $request)
    {
        $stores = $this->storesForCurrentUser();
        $definitions = $this->getObjectiveDefinitions();
        $year = (int) $request->input('year', date('Y'));
        $storeId = $request->input('store_id');
        if ($storeId !== null && $storeId !== '') {
            $request->validate(['store_id' => 'exists:stores,id']);
        } else {
            $storeId = null;
        }

        $monthKeys = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $months = collect($monthKeys)->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::createFromDate(2000, (int) $m, 1)->locale('es')->monthName]);

        $settings = MonthlyObjectiveSetting::with('settingValues')
            ->where('store_id', $storeId)
            ->where('year', $year)
            ->get()
            ->keyBy('month');

        $settingsByMonth = [];
        foreach ($monthKeys as $key) {
            $row = $settings->get($key);
            $byDef = [];
            foreach ($definitions as $def) {
                $val = $row ? $row->settingValues->firstWhere('objective_definition_id', $def->id) : null;
                $byDef[$def->id] = $val ? (float) $val->percentage : 0.0;
            }
            $settingsByMonth[$key] = $byDef;
        }

        $currentYear = (int) date('Y');
        $nextYear = $currentYear + 1;
        $yearsFromDb = MonthlyObjectiveSetting::select('year')->distinct()->pluck('year');
        $availableYears = collect([$currentYear, $nextYear])
            ->merge($yearsFromDb)
            ->push($year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('settings.objectives.index', compact('stores', 'storeId', 'year', 'availableYears', 'months', 'settingsByMonth', 'definitions'));
    }

    /**
     * Guardar porcentajes por mes (un valor por cada objetivo definido).
     */
    public function store(Request $request)
    {
        $request->merge(['store_id' => $request->input('store_id') ?: null]);
        $storeId = $this->enforcedStoreIdForCreate($request->input('store_id') ? (int) $request->input('store_id') : null);
        $year = (int) $request->input('year', date('Y'));
        if ($storeId !== null) {
            $request->validate(['store_id' => 'exists:stores,id']);
        }

        $definitions = $this->getObjectiveDefinitions();
        if ($definitions->isEmpty()) {
            return redirect()->route('objectives-settings.index', ['year' => $year, 'store_id' => $storeId])
                ->with('error', 'Añade al menos un objetivo en la sección superior antes de guardar porcentajes.');
        }

        $monthKeys = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $rules = ['year' => 'required|integer|min:2000|max:2100'];
        foreach ($monthKeys as $key) {
            foreach ($definitions as $def) {
                $rules["months.{$key}.objectives.{$def->id}"] = 'required|numeric|min:0';
            }
        }
        $validated = $request->validate($rules);
        $year = (int) $validated['year'];

        foreach ($monthKeys as $key) {
            $setting = MonthlyObjectiveSetting::updateOrCreate(
                ['store_id' => $storeId, 'year' => $year, 'month' => $key],
                []
            );
            foreach ($definitions as $def) {
                $pct = (float) ($validated['months'][$key]['objectives'][$def->id] ?? 0);
                MonthlyObjectiveSettingValue::updateOrCreate(
                    [
                        'monthly_objective_setting_id' => $setting->id,
                        'objective_definition_id' => $def->id,
                    ],
                    ['percentage' => $pct]
                );
            }
        }

        $message = $storeId ? 'Configuración por tienda guardada.' : 'Configuración general guardada.';
        return redirect()->route('objectives-settings.index', ['year' => $year, 'store_id' => $storeId])->with('success', $message);
    }

    public function destroy(MonthlyObjectiveSetting $objectives_setting)
    {
        $this->authorizeStoreAccess($objectives_setting->store_id);
        $objectives_setting->delete();
        return redirect()->route('objectives-settings.index')->with('success', 'Configuración eliminada.');
    }

    /**
     * Añadir un nuevo objetivo (nombre) para la empresa actual.
     */
    public function storeObjectiveDefinition(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);
        $companyId = $this->currentCompanyId();
        if (! $companyId) {
            return redirect()->route('objectives-settings.index')->with('error', 'No hay empresa seleccionada.');
        }
        $maxOrder = ObjectiveDefinition::where('company_id', $companyId)->max('sort_order') ?? 0;
        ObjectiveDefinition::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'sort_order' => $maxOrder + 1,
        ]);
        return redirect()->route('objectives-settings.index')->with('success', 'Objetivo añadido. Configura los porcentajes por mes y guarda.');
    }

    /**
     * Eliminar un objetivo. Se eliminan también sus porcentajes guardados.
     */
    public function destroyObjectiveDefinition(ObjectiveDefinition $objective_definition)
    {
        $companyId = $this->currentCompanyId();
        if ($companyId === null || $objective_definition->company_id != $companyId) {
            abort(403);
        }
        $objective_definition->delete();
        return redirect()->route('objectives-settings.index')->with('success', 'Objetivo eliminado. El módulo de objetivos mensuales se actualizará.');
    }
}
