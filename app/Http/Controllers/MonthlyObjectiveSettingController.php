<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\MonthlyObjectiveSetting;
use App\Models\Store;
use Illuminate\Http\Request;

class MonthlyObjectiveSettingController extends Controller
{
    use EnforcesStoreScope;

    public function __construct()
    {
        $this->middleware('permission:settings.objectives.view')->only(['index']);
        $this->middleware('permission:settings.objectives.create')->only(['store']);
        $this->middleware('permission:settings.objectives.delete')->only(['destroy']);
    }

    /**
     * Muestra la sección Objetivos de ventas: filtro por año y opcional tienda,
     * una fila por mes (enero–diciembre) con % objetivo 1 y 2.
     */
    public function index(Request $request)
    {
        $stores = $this->storesForCurrentUser();
        $year = (int) $request->input('year', date('Y'));
        $storeId = $request->input('store_id');
        if ($storeId !== null && $storeId !== '') {
            $request->validate(['store_id' => 'exists:stores,id']);
        } else {
            $storeId = null;
        }

        $monthKeys = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $months = collect($monthKeys)->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::createFromDate(2000, (int) $m, 1)->locale('es')->monthName]);

        $settings = MonthlyObjectiveSetting::where('store_id', $storeId)
            ->where('year', $year)
            ->get()
            ->keyBy('month');
        $settingsByMonth = [];
        foreach ($monthKeys as $key) {
            $row = $settings->get($key);
            $settingsByMonth[$key] = [
                'percentage_objective_1' => $row ? (float) $row->percentage_objective_1 : 0,
                'percentage_objective_2' => $row ? (float) $row->percentage_objective_2 : 0,
            ];
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

        return view('settings.objectives.index', compact('stores', 'storeId', 'year', 'availableYears', 'months', 'settingsByMonth'));
    }

    /**
     * Guardar los 12 meses de una vez. year obligatorio, store_id opcional (null = configuración general).
     */
    public function store(Request $request)
    {
        $request->merge(['store_id' => $request->input('store_id') ?: null]);
        $storeId = $this->enforcedStoreIdForCreate($request->input('store_id') ? (int) $request->input('store_id') : null);
        $year = (int) $request->input('year', date('Y'));
        if ($storeId !== null) {
            $request->validate(['store_id' => 'exists:stores,id']);
        }

        $monthKeys = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $rules = [
            'year' => 'required|integer|min:2000|max:2100',
        ];
        foreach ($monthKeys as $key) {
            $rules["months.{$key}.percentage_objective_1"] = 'required|numeric|min:0';
            $rules["months.{$key}.percentage_objective_2"] = 'required|numeric|min:0';
        }
        $validated = $request->validate($rules);
        $year = (int) $validated['year'];

        foreach ($monthKeys as $key) {
            $pct1 = (float) ($validated['months'][$key]['percentage_objective_1'] ?? 0);
            $pct2 = (float) ($validated['months'][$key]['percentage_objective_2'] ?? 0);
            MonthlyObjectiveSetting::updateOrCreate(
                ['store_id' => $storeId, 'year' => $year, 'month' => $key],
                [
                    'percentage_objective_1' => $pct1,
                    'percentage_objective_2' => $pct2,
                ]
            );
        }

        $message = $storeId ? 'Configuración por tienda guardada.' : 'Configuración general (todos los meses) guardada.';
        return redirect()->route('objectives-settings.index', ['year' => $year, 'store_id' => $storeId])->with('success', $message);
    }

    public function destroy(MonthlyObjectiveSetting $objectives_setting)
    {
        $this->authorizeStoreAccess($objectives_setting->store_id);
        $objectives_setting->delete();
        return redirect()->route('objectives-settings.index')->with('success', 'Configuración eliminada.');
    }
}
