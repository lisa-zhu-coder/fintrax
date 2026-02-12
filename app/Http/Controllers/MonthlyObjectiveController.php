<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnforcesStoreScope;
use App\Models\FinancialEntry;
use App\Models\MonthlyObjectiveSetting;
use App\Models\ObjectiveDailyRow;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyObjectiveController extends Controller
{
    use EnforcesStoreScope;
    public function __construct()
    {
        $this->middleware('permission:objectives.main.view')->only(['index', 'storeMonths', 'monthDetail']);
        $this->middleware('permission:objectives.main.create')->only(['importForm', 'downloadTemplate', 'processImport']);
        $this->middleware('permission:objectives.main.edit')->only(['updateMonthBases', 'updateBase']);
    }

    /**
     * Vista principal: tabla por tiendas (Objetivo 1, Objetivo 2, Cumplido, Diferencias).
     */
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $stores = Store::orderBy('name')->get();
        $storeData = [];
        foreach ($stores as $store) {
            $rows = ObjectiveDailyRow::where('store_id', $store->id)
                ->where('month', 'like', $year . '-%')
                ->get();
            $obj1 = $obj2 = $cumplido = 0;
            foreach ($rows as $row) {
                $monthNum = (int) substr($row->month, 5, 2);
                [$pct1, $pct2] = MonthlyObjectiveSetting::getPercentagesForStoreMonth($store->id, (string) $monthNum, $year);
                $base = (float) $row->base_2025;
                $obj1 += $base * (1 + $pct1 / 100);
                $obj2 += $base * (1 + $pct2 / 100);
                $cumplido += $this->getDailyCloseAmount($store->id, $row->date_2026);
            }
            $storeData[$store->id] = [
                'obj1' => $obj1,
                'obj2' => $obj2,
                'cumplido' => $cumplido,
                'diff1' => $cumplido - $obj1,
                'diff2' => $cumplido - $obj2,
            ];
        }
        $availableYears = $this->availableYears();
        return view('objectives.index', compact('stores', 'storeData', 'year', 'availableYears'));
    }

    /**
     * Vista por tienda: tabla de los 12 meses del año.
     * No depende de objective_daily_rows: si no hay filas, se muestran 0.
     * Las filas diarias se crean solo al entrar en un mes concreto (monthDetail).
     */
    public function storeMonths(Store $store, int $year)
    {
        $this->authorizeStoreAccess($store->id);
        $monthsData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStr = sprintf('%04d-%02d', $year, $month);
            $rows = ObjectiveDailyRow::where('store_id', $store->id)->where('month', $monthStr)->get();

            $obj1 = $obj2 = $cumplido = 0;
            if (! $rows->isEmpty()) {
                [$pct1, $pct2] = MonthlyObjectiveSetting::getPercentagesForStoreMonth($store->id, (string) $month, $year);
                foreach ($rows as $row) {
                    $base = (float) $row->base_2025;
                    $obj1 += $base * (1 + $pct1 / 100);
                    $obj2 += $base * (1 + $pct2 / 100);
                    $cumplido += $this->getDailyCloseAmount($store->id, $row->date_2026);
                }
            }

            $monthsData[] = (object) [
                'month' => $month,
                'monthName' => Carbon::createFromDate($year, $month, 1)->locale('es')->monthName,
                'year' => $year,
                'obj1' => $obj1,
                'obj2' => $obj2,
                'cumplido' => $cumplido,
                'diff1' => $cumplido - $obj1,
                'diff2' => $cumplido - $obj2,
            ];
        }
        return view('objectives.store-months', compact('store', 'year', 'monthsData'));
    }

    /**
     * Vista diaria del mes. Auto-crea filas si no existen.
     */
    public function monthDetail(Store $store, int $year, int $month)
    {
        $monthStr = sprintf('%04d-%02d', $year, $month);
        $rows = ObjectiveDailyRow::where('store_id', $store->id)->where('month', $monthStr)->orderBy('date_2026')->get();
        if ($rows->isEmpty()) {
            $this->createRowsForMonth($store->id, $year, $month);
            $rows = ObjectiveDailyRow::where('store_id', $store->id)->where('month', $monthStr)->orderBy('date_2026')->get();
        }
        [$pct1, $pct2] = MonthlyObjectiveSetting::getPercentagesForStoreMonth($store->id, (string) $month, $year);
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('es')->monthName;
        $rowsWithCalcs = $rows->map(function ($row) use ($store, $pct1, $pct2) {
            $base = (float) $row->base_2025;
            $obj1 = $base * (1 + $pct1 / 100);
            $obj2 = $base * (1 + $pct2 / 100);
            $cumplido = $this->getDailyCloseAmount($store->id, $row->date_2026);
            return (object) [
                'row' => $row,
                'obj1' => $obj1,
                'obj2' => $obj2,
                'cumplido' => $cumplido,
                'diff1' => $cumplido - $obj1,
                'diff2' => $cumplido - $obj2,
            ];
        });
        // Resumen mensual (dinámico, misma lógica que vista por meses y por tienda)
        $totalObj1 = $rowsWithCalcs->sum('obj1');
        $totalObj2 = $rowsWithCalcs->sum('obj2');
        $totalCumplido = $rowsWithCalcs->sum('cumplido');
        $summary = (object) [
            'total_obj1' => $totalObj1,
            'total_obj2' => $totalObj2,
            'total_cumplido' => $totalCumplido,
            'diff1' => $totalCumplido - $totalObj1,
            'diff2' => $totalCumplido - $totalObj2,
        ];
        return view('objectives.month', compact('store', 'year', 'month', 'monthName', 'rowsWithCalcs', 'pct1', 'pct2', 'summary'));
    }

    /**
     * Actualizar todas las bases 2025 del mes de una vez.
     */
    public function updateMonthBases(Request $request, Store $store, int $year, int $month)
    {
        $this->authorizeStoreAccess($store->id);
        $validated = $request->validate([
            'bases' => 'array',
            'bases.*' => 'nullable|numeric|min:0',
        ]);

        $monthStr = sprintf('%04d-%02d', $year, $month);
        $rows = ObjectiveDailyRow::where('store_id', $store->id)->where('month', $monthStr)->get();
        $bases = $validated['bases'] ?? [];

        foreach ($rows as $row) {
            if (array_key_exists((string) $row->id, $bases)) {
                $row->update(['base_2025' => (float) ($bases[$row->id] ?? 0)]);
            }
        }

        return redirect()->route('objectives.month', ['store' => $store, 'year' => $year, 'month' => $month])
            ->with('success', 'Bases del mes guardadas.');
    }

    /**
     * Formulario de importación CSV: elegir tienda, descargar plantilla, subir archivo.
     */
    public function importForm()
    {
        $stores = Store::orderBy('name')->get();
        return view('objectives.import', compact('stores'));
    }

    /**
     * Descargar plantilla CSV con cabeceras: Fecha 2025 (dd/mm/yyyy), Base 2025.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_bases_2025.csv"',
        ];
        $callback = function () {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($out, ['Fecha 2025 (dd/mm/yyyy)', 'Base 2025'], ';');
            fputcsv($out, ['02/01/2025', '0'], ';');
            fputcsv($out, ['03/01/2025', '0'], ';');
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Procesar CSV importado: actualizar base_2025 por tienda y fecha 2025.
     */
    public function processImport(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $storeId = (int) $this->enforcedStoreIdForCreate((int) $validated['store_id']);
        if ($storeId === null) {
            return redirect()->back()->withInput()->withErrors(['store_id' => 'Debe seleccionar una tienda válida.']);
        }
        $this->authorizeStoreAccess($storeId);
        $path = $request->file('file')->getRealPath();
        $updated = 0;
        $errors = [];

        $handle = fopen($path, 'r');
        if (! $handle) {
            return redirect()->route('objectives.import')->with('error', 'No se pudo leer el archivo.');
        }

        $first = true;
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Si la línea no es UTF-8 válido, intentar Windows-1252 (Excel en español)
            if (! mb_check_encoding($line, 'UTF-8')) {
                $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1252');
            }
            $delimiter = ';';
            if (strpos($line, ';') !== false && strpos($line, ',') === false) {
                $delimiter = ';';
            } elseif (strpos($line, "\t") !== false) {
                $delimiter = "\t";
            } else {
                $delimiter = ',';
            }
            $row = str_getcsv($line, $delimiter);
            if ($first) {
                $first = false;
                if (count($row) >= 2 && (stripos($row[0], 'fecha') !== false || stripos($row[0], 'date') !== false)) {
                    continue; // skip header
                }
            }
            if (count($row) < 2) {
                continue;
            }
            $col0 = trim(trim($row[0]), " \t\"'");
            $col1 = trim(trim($row[1]), " \t\"'");
            // Si la primera columna parece número y la segunda parece fecha, intercambiar (CSV con columnas al revés)
            if (preg_match('/^\d+[,.\s]?\d*$/', preg_replace('/\s/', '', $col0)) && preg_match('/\d{1,4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,4}/', $col1)) {
                [$col0, $col1] = [$col1, $col0];
            }
            $dateStr = preg_replace('/^\xEF\xBB\xBF/', '', $col0);
            $dateStr = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $dateStr);
            $amountStr = preg_replace('/^\xEF\xBB\xBF/', '', $col1);
            $date = $this->parseDate2025($dateStr);
            if (! $date) {
                $errors[] = "Fecha no válida: {$dateStr}";
                continue;
            }
            $amount = $this->parseAmount($amountStr);
            if ($amount < 0) {
                $amount = 0;
            }
            $dateYmd = $date->format('Y-m-d');
            $rowModel = ObjectiveDailyRow::where('store_id', $storeId)->whereDate('date_2025', $dateYmd)->first();
            if ($rowModel) {
                $rowModel->update(['base_2025' => $amount]);
                $updated++;
            }
        }
        fclose($handle);

        if (! empty($errors)) {
            return redirect()->route('objectives.import')
                ->with('error', 'Importación con errores: ' . implode(' ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '…' : ''))
                ->with('imported_count', $updated);
        }

        $message = $updated > 0 ? "Se han actualizado {$updated} bases 2025." : 'No se encontraron filas coincidentes para actualizar. Comprueba que las fechas existan en los objetivos de la tienda.';
        return redirect()->route('objectives.import')->with('success', $message);
    }

    /**
     * Parsea importe aceptando formato europeo (5.202,50) y US (5,202.50).
     */
    private function parseAmount(string $value): float
    {
        $value = trim(str_replace(' ', '', $value));
        if ($value === '') {
            return 0.0;
        }
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        if ($lastComma !== false && $lastDot !== false) {
            // Hay coma y punto: el que va último es el decimal
            if ($lastComma > $lastDot) {
                // Europeo: 5.202,50 → quitar punto (miles), coma → punto
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // US: 5,202.50 → quitar coma (miles)
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            // Solo coma: decimal (5202,50)
            $value = str_replace(',', '.', $value);
        }
        // Solo punto: 5202.50 ya es válido; 5.202 sería miles pero (float)"5.202"=5.202, así que si quieren 5202 deben usar 5202 o 5.202,00
        return (float) $value;
    }

    private function parseDate2025(string $value): ?Carbon
    {
        $value = trim($value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim($value, " \t\"'\r\n");
        // Normalizar dígitos/símbolos Unicode (Excel o copiar/pegar)
        $value = str_replace(['０', '１', '２', '３', '４', '５', '６', '７', '８', '９', '／', '－', '．'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '/', '-', '.'], $value);
        if ($value === '') {
            return null;
        }
        // Quitar hora si viene (02/01/2025 00:00:00 o 02/01/2025 0:00)
        if (preg_match('/^(.+?)\s+\d{1,2}:\d{1,2}(:\d{1,2})?/', $value, $m)) {
            $value = trim($m[1]);
        }
        // Excel exporta fechas como número serial (entero o decimal 45321 o 45321.0)
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            $serial = (int) floor((float) $value);
            if ($serial >= 25569) {
                $d = Carbon::createFromDate(1899, 12, 30)->addDays($serial);
                if ($d->year >= 2000 && $d->year <= 2030) {
                    return $d;
                }
            }
        }
        // Prioridad: formato español día/mes/año (d/m/Y) para evitar 02/01 → 1 feb en US
        $formatsEspanol = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'j-n-Y', 'd/m/y', 'j/n/y', 'd/m/Y H:i', 'd-m-Y H:i', 'd/m/Y H:i:s', 'd-m-Y H:i:s'];
        foreach ($formatsEspanol as $format) {
            $d = @Carbon::createFromFormat($format, $value);
            if ($d && $d->year >= 2000 && $d->year <= 2030) {
                return $d;
            }
        }
        // ISO y otros
        $formatsOtros = ['Y-m-d', 'Y/m/d', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formatsOtros as $format) {
            $d = @Carbon::createFromFormat($format, $value);
            if ($d && $d->year >= 2000 && $d->year <= 2030) {
                return $d;
            }
        }
        // strtotime() es muy flexible (acepta "2 January 2025", "02 Jan 2025", etc.)
        $ts = @strtotime($value);
        if ($ts !== false) {
            $d = Carbon::createFromTimestamp($ts);
            if ($d->year >= 2000 && $d->year <= 2030) {
                return $d;
            }
        }
        // Carbon::parse con locale español (nombres de mes en español)
        $prevLocale = Carbon::getLocale();
        try {
            Carbon::setLocale('es');
            $d = Carbon::parse($value);
            if ($d->year >= 2000 && $d->year <= 2030) {
                return $d;
            }
        } catch (\Exception $e) {
            // ignore
        } finally {
            Carbon::setLocale($prevLocale);
        }
        try {
            $d = Carbon::parse($value);
            if ($d->year >= 2000 && $d->year <= 2030) {
                return $d;
            }
        } catch (\Exception $e) {
            // ignore
        }
        return null;
    }

    /**
     * Actualizar base_2025 de una fila (AJAX o form).
     */
    public function updateBase(Request $request, ObjectiveDailyRow $objectiveDailyRow)
    {
        $validated = $request->validate([
            'base_2025' => 'required|numeric|min:0',
        ]);
        $objectiveDailyRow->update(['base_2025' => $validated['base_2025']]);
        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        $d = $objectiveDailyRow->date_2026;
        return redirect()->route('objectives.month', [
            'store' => $objectiveDailyRow->store_id,
            'year' => $d->year,
            'month' => $d->month,
        ])->with('success', 'Base actualizada.');
    }

    /**
     * Objetivo cumplido = suma del IMPORTE TOTAL (total_amount) de cierres diarios.
     * Cálculo dinámico en tiempo real: no se guarda en BD.
     * WHERE: store_id, date = date_2026, type = 'daily_close'.
     */
    private function getDailyCloseAmount(int $storeId, $date): float
    {
        $dateStr = $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;
        $total = FinancialEntry::where('type', 'daily_close')
            ->where('store_id', $storeId)
            ->whereDate('date', $dateStr)
            ->get()
            ->sum(fn ($entry) => (float) ($entry->total_amount ?? $entry->amount ?? 0));
        return $total;
    }

    /**
     * Crea filas diarias del mes. date_2025 = date_2026 - 1 año + 1 día, para que coincidan los días de la semana:
     * Ene 2026 (01/01-31/01) ↔ 2025: 02/01-01/02 | Feb 2026 (01/02-28/02) ↔ 2025: 02/02-01/03 | etc.
     */
    private function createRowsForMonth(int $storeId, int $year, int $month): void
    {
        $start = Carbon::createFromDate($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $monthStr = sprintf('%04d-%02d', $year, $month);
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $date2026 = $d->format('Y-m-d');
            $date2025 = $d->copy()->subYear()->addDay()->format('Y-m-d');
            $weekdayName = $d->locale('es')->dayName;
            ObjectiveDailyRow::create([
                'store_id' => $storeId,
                'month' => $monthStr,
                'date_2025' => $date2025,
                'date_2026' => $date2026,
                'weekday' => $weekdayName,
                'base_2025' => 0,
            ]);
        }
    }

    private function availableYears(): \Illuminate\Support\Collection
    {
        $years = ObjectiveDailyRow::get()->pluck('month')->map(fn ($m) => (int) substr($m, 0, 4))->unique()->sortDesc()->values();
        if ($years->isEmpty() || ! $years->contains(now()->year)) {
            $years = $years->prepend(now()->year)->unique()->sortDesc()->values();
        }
        return $years;
    }
}
