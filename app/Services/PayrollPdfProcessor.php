<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser as PdfParser;

class PayrollPdfProcessor
{
    /**
     * Devuelve el número de páginas del PDF (rápido, sin procesar contenido).
     */
    public function getPageCount(string $storagePath): int
    {
        $path = Storage::disk('local')->path($storagePath);
        return is_readable($path) ? $this->getPdfPageCount($path) : 0;
    }

    /**
     * Procesa un PDF multipágina y devuelve los datos para pending_payroll_uploads.
     * Ejecutar en job para no bloquear la petición.
     *
     * @return array{ pending: array, upload_id: string, saved: int, failed_pages: array, message: string }|array{ error: string }
     */
    public function processMultiPage(string $storagePath, string $originalFileName, int $companyId): array
    {
        $path = Storage::disk('local')->path($storagePath);
        if (!is_readable($path)) {
            return ['error' => 'Archivo no encontrado.'];
        }

        set_time_limit(300);
        if (ini_get('memory_limit') !== '-1') {
            @ini_set('memory_limit', '512M');
        }

        $pagesText = $this->getPayrollPdfTextPerPage($path);
        if (empty($pagesText)) {
            return ['error' => 'No se pudo leer el PDF. Comprueba que el archivo no esté corrupto o protegido.'];
        }

        $pageCount = $this->getPdfPageCount($path);
        if ($pageCount <= 0) {
            return ['error' => 'No se pudo procesar el PDF.'];
        }

        $dateFromFileName = $this->extractDateFromFileName($originalFileName);
        $uploadId = uniqid('payroll_', true);
        $pending = [];
        $saved = 0;
        $failedPages = [];
        $lastEmployee = null;
        $originalBase = pathinfo($originalFileName, PATHINFO_FILENAME);

        for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
            $pageText = $pagesText[$pageNum - 1] ?? '';
            $employee = $this->findEmployeeByFile($originalFileName, $pageText, $companyId);
            if (!$employee) {
                $failedPages[] = $pageNum;
                continue;
            }

            $singlePageBase64 = $this->extractSinglePageAsBase64($path, $pageNum);
            if ($singlePageBase64 === null) {
                $failedPages[] = $pageNum;
                continue;
            }

            $date = $this->extractDateFromPageText($pageText) ?? $dateFromFileName;
            if (!($date instanceof \Carbon\Carbon)) {
                $date = $dateFromFileName;
            }
            $month = (int) $date->month;
            $year = (int) $date->year;
            $pageBase = $originalBase . ' p' . $pageNum;
            $fileName = $this->suggestedPayrollFileName($pageBase, $employee->full_name);
            $tempPath = 'temp_payrolls/' . $uploadId . '/page_' . $pageNum . '.pdf';
            Storage::disk('local')->put($tempPath, base64_decode($singlePageBase64, true));

            $index = count($pending);
            $pending[$index] = [
                'temp_path' => $tempPath,
                'employee_id' => $employee->id,
                'file_name' => $fileName,
                'original_base' => $pageBase,
                'month' => $month,
                'year' => $year,
                'date' => $date->format('Y-m-d'),
            ];
            $saved++;
            $lastEmployee = $employee;
        }

        if ($saved === 0) {
            $message = 'No se pudo identificar a ningún empleado en el PDF. ';
            if (!empty($failedPages)) {
                $message .= 'Asegúrate de que cada nómina contenga el nombre, DNI o número de la seguridad social del empleado.';
            }
            return ['error' => $message];
        }

        $message = $saved === 1
            ? '1 nómina lista para enviar.'
            : $saved . ' nóminas listas para enviar.';
        if (!empty($failedPages)) {
            $message .= ' No se pudo asignar la(s) página(s) ' . implode(', ', $failedPages) . '.';
        }

        return [
            'pending' => $pending,
            'upload_id' => $uploadId,
            'saved' => $saved,
            'failed_pages' => $failedPages,
            'message' => $message,
        ];
    }

    private function findEmployeeByFile(string $fileName, string $pdfText, int $companyId)
    {
        $fileNameLower = mb_strtolower($fileName);
        $allText = mb_strtolower($fileName . ' ' . $pdfText);

        $employees = Employee::where('company_id', $companyId)->get();

        foreach ($employees as $employee) {
            if ($employee->dni) {
                $dniNormalized = preg_replace('/[-\s]/', '', mb_strtoupper($employee->dni));
                $dniLower = mb_strtolower($dniNormalized);
                if (mb_strpos($allText, $dniLower) !== false) {
                    return $employee;
                }
            }
            if ($employee->social_security) {
                $ssNormalized = preg_replace('/[-\s]/', '', $employee->social_security);
                $ssLower = mb_strtolower($ssNormalized);
                if (mb_strpos($allText, $ssLower) !== false) {
                    return $employee;
                }
            }
            if ($employee->full_name) {
                $nameParts = array_filter(explode(' ', mb_strtolower($employee->full_name)), fn ($part) => mb_strlen(trim($part)) > 2);
                $matches = 0;
                foreach ($nameParts as $part) {
                    $part = trim($part);
                    if (mb_strlen($part) > 2 && mb_strpos($allText, $part) !== false) {
                        $matches++;
                    }
                }
                if ($matches >= 2) {
                    return $employee;
                }
            }
        }
        return null;
    }

    private function getPayrollPdfTextPerPage(string $path): array
    {
        $texts = [];
        try {
            if (!class_exists(PdfParser::class)) {
                return $texts;
            }
            $parser = new PdfParser();
            $document = $parser->parseFile($path);
            foreach ($document->getPages() as $page) {
                $texts[] = $page->getText() ?? '';
            }
        } catch (\Exception $e) {
            Log::warning('Error extrayendo texto del PDF de nómina: ' . $e->getMessage());
        }
        return $texts;
    }

    private function getPdfPageCount(string $path): int
    {
        try {
            $fpdi = new Fpdi();
            return $fpdi->setSourceFile($path);
        } catch (\Exception $e) {
            Log::warning('Error leyendo número de páginas del PDF: ' . $e->getMessage());
            return 0;
        }
    }

    private function extractSinglePageAsBase64(string $path, int $pageNumber): ?string
    {
        try {
            $fpdi = new Fpdi();
            $fpdi->setSourceFile($path);
            $tplId = $fpdi->importPage($pageNumber);
            $fpdi->AddPage();
            $fpdi->useTemplate($tplId, 0, 0, null, null, true);
            return base64_encode($fpdi->Output('S', ''));
        } catch (\Exception $e) {
            Log::warning('Error extrayendo página ' . $pageNumber . ' del PDF: ' . $e->getMessage());
            return null;
        }
    }

    private function extractDateFromPageText(string $text): ?\Carbon\Carbon
    {
        if (trim($text) === '') {
            return null;
        }
        $textNorm = preg_replace('/\s+/', ' ', $text);
        $monthsMap = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
            'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
        ];
        if (preg_match('/periodo\s+de\s+liquidaci[oó]n\s+.*?\b(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+al\s+\d{1,2}\s+de\s+\2\s+de\s+(\d{4})\b/iu', $textNorm, $m)) {
            $monthName = mb_strtolower($m[2]);
            if (isset($monthsMap[$monthName])) {
                $year = (int) $m[3];
                if ($year >= 2000 && $year <= 2100) {
                    return \Carbon\Carbon::createFromDate($year, $monthsMap[$monthName], 1);
                }
            }
        }
        if (preg_match('/periodo\s+de\s+liquidaci[oó]n\s+.*?(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/iu', $textNorm, $m)) {
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                return \Carbon\Carbon::createFromDate($year, $month, 1);
            }
        }
        if (preg_match('/periodo[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/iu', $textNorm, $m)) {
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                return \Carbon\Carbon::createFromDate($year, $month, 1);
            }
        }
        if (preg_match('/n[oó]mina[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/iu', $textNorm, $m)) {
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                return \Carbon\Carbon::createFromDate($year, $month, 1);
            }
        }
        return null;
    }

    private function extractDateFromFileName(string $fileName): \Carbon\Carbon
    {
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fileNameLower = mb_strtolower($fileName);
        foreach ($months as $index => $month) {
            if (mb_strpos($fileNameLower, $month) !== false && preg_match('/\b(20\d{2})\b/', $fileName, $matches)) {
                return \Carbon\Carbon::createFromDate($matches[1], $index + 1, 1);
            }
        }
        if (preg_match('/(\d{4})[\/\-](\d{1,2})/', $fileName, $matches)) {
            return \Carbon\Carbon::createFromDate($matches[1], $matches[2], 1);
        }
        return now();
    }

    private function suggestedPayrollFileName(string $originalBase, string $employeeFullName): string
    {
        $base = trim(preg_replace('/[\\\\\/:*?"<>|]/', '', $originalBase));
        $name = trim(preg_replace('/\s+/', ' ', $employeeFullName));
        return trim(preg_replace('/\s+/', ' ', $base . ' ' . $name . '.pdf'));
    }
}
