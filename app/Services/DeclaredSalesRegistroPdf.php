<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Genera el PDF "Registro de ventas" con la estructura y diseño de la plantilla LT.
 * Una página por tienda, sin usar FPDI: todo dibujado desde cero para evitar duplicados y desalineación.
 */
class DeclaredSalesRegistroPdf extends \FPDF
{
    private array $rowsByStore;

    private string $companyName;

    private Carbon $monthStart;

    private array $spanWeekdays = [
        'Monday' => 'lunes', 'Tuesday' => 'martes', 'Wednesday' => 'miércoles', 'Thursday' => 'jueves',
        'Friday' => 'viernes', 'Saturday' => 'sábado', 'Sunday' => 'domingo',
    ];

    private const WHITE = [255, 255, 255];
    private const BLACK = [0, 0, 0];

    /** Convierte config (array [R,G,B] o hex '#RRGGBB') a [R, G, B]. */
    private function parseColor(array|string $value, array $default): array
    {
        if (is_array($value) && count($value) === 3) {
            return array_map('intval', $value);
        }
        if (is_string($value) && preg_match('/^#([0-9A-Fa-f]{6})$/', $value, $m)) {
            return [
                (int) hexdec(substr($m[1], 0, 2)),
                (int) hexdec(substr($m[1], 2, 2)),
                (int) hexdec(substr($m[1], 4, 2)),
            ];
        }
        return $default;
    }

    private function barColor(): array
    {
        return $this->parseColor(
            config('declared_sales_pdf.colors.bar', [60, 60, 60]),
            [60, 60, 60]
        );
    }

    private function accentColor(): array
    {
        return $this->parseColor(
            config('declared_sales_pdf.colors.accent', [150, 70, 150]),
            [150, 70, 150]
        );
    }

    public function __construct(array $dailyRows, string $companyName, Carbon $monthStart)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->companyName = $companyName;
        $this->monthStart = $monthStart->copy()->startOfMonth();
        $this->rowsByStore = $this->groupRowsByStore($dailyRows);
        $this->SetAutoPageBreak(false);
        $this->SetMargins(18, 0, 18);
        $this->SetFont('Helvetica', '', 10);
    }

    private function groupRowsByStore(array $dailyRows): array
    {
        $byStore = [];
        foreach ($dailyRows as $row) {
            $id = $row['store_id'];
            if (! isset($byStore[$id])) {
                $byStore[$id] = [
                    'store_name' => $row['store_name'],
                    'rows' => [],
                ];
            }
            $byStore[$id]['rows'][] = $row;
        }
        foreach ($byStore as $id => &$data) {
            usort($data['rows'], fn ($a, $b) => $a['date']->format('Y-m-d') <=> $b['date']->format('Y-m-d'));
        }
        return $byStore;
    }

    private function enc(string $s): string
    {
        if ($s === '') {
            return '';
        }
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
        return $out !== false ? $out : $s;
    }

    private function monthNameFor(int $monthNum): string
    {
        $names = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $names[$monthNum] ?? '';
    }

    private function formatDate(Carbon $date): string
    {
        $date = $date->copy();
        $weekday = $this->spanWeekdays[$date->format('l')] ?? $date->format('l');
        $month = $this->monthNameFor((int) $date->format('n'));
        $day = $date->format('d');
        $year = $date->format('Y');
        return $weekday . ', ' . $month . ' ' . $day . ', ' . $year;
    }

    public function build(): void
    {
        foreach ($this->rowsByStore as $storeId => $data) {
            $this->AddPage();
            $this->drawStorePage($data['store_name'], $data['rows']);
        }
    }

    /**
     * Dibuja una página con el diseño de la plantilla LT:
     * Barra gris con nombre en blanco, título rosa/violeta, dos cajas rosa/violeta con totales en blanco,
     * tabla con cabecera y TOTAL en rosa/violeta y texto blanco.
     */
    private function drawStorePage(string $storeName, array $rows): void
    {
        $totalWithVat = 0;
        $totalWithoutVat = 0;
        $totalTpv = 0;
        $totalEfectivo = 0;
        foreach ($rows as $r) {
            $totalTpv += $r['bank_amount'];
            $totalEfectivo += $r['cash_amount'];
            $totalWithVat += $r['total_with_vat'];
            $totalWithoutVat += $r['total_without_vat'];
        }

        $left = 18;
        $pageW = 210;
        $pageH = 297;
        $bottomMargin = 15;
        $wFecha = 70;
        $wCol = 27;
        $barH = 11;
        $boxH = 18;
        $boxW = 88;
        $nRows = max(count($rows), 1);
        $headerH = 6;
        $totalRowH = 6;
        $tableTop = 72;
        $tableHeight = $pageH - $tableTop - $bottomMargin;
        $rowH = ($tableHeight - $headerH - $totalRowH) / $nRows;

        $bar = $this->barColor();
        $accent = $this->accentColor();

        // 1. Barra superior con nombre de tienda en blanco
        $this->SetFillColor($bar[0], $bar[1], $bar[2]);
        $this->Rect(0, 0, $pageW, $barH, 'F');
        $this->SetTextColor(self::WHITE[0], self::WHITE[1], self::WHITE[2]);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetXY($left, ($barH - 5) / 2);
        $this->Cell(0, 5, $this->enc(mb_strtoupper($storeName)), 0, 1, 'L', false);

        // 2. Título REGISTRO VENTAS [MES] [AÑO] más grande y en negrita
        $this->SetY($barH + 5);
        $this->SetTextColor($accent[0], $accent[1], $accent[2]);
        $titulo = 'REGISTRO VENTAS ' . strtoupper($this->monthNameFor((int) $this->monthStart->format('n'))) . ' ' . $this->monthStart->format('Y');
        $this->SetFont('Helvetica', 'B', 24);
        $this->Cell(0, 12, $this->enc($titulo), 0, 1, 'L', false);
        $this->Ln(10);

        // 3. Etiquetas "Total con IVA" y "Total sin IVA" sobre las cajas (texto negro, negrita)
        $this->SetTextColor(self::BLACK[0], self::BLACK[1], self::BLACK[2]);
        $this->SetFont('Helvetica', 'B', 12);
        $this->Cell($boxW, 6, $this->enc('Total con IVA'), 0, 0, 'L', false);
        $this->Cell(10, 6, '', 0, 0, 'L', false);
        $this->Cell($boxW, 6, $this->enc('Total sin IVA'), 0, 1, 'L', false);

        // 4. Dos cajas (color accent) con importes en blanco
        $yBox = $this->GetY();
        $this->SetFillColor($accent[0], $accent[1], $accent[2]);
        $this->Rect($left, $yBox, $boxW, $boxH, 'F');
        $this->Rect($left + $boxW + 10, $yBox, $boxW, $boxH, 'F');
        $this->SetTextColor(self::WHITE[0], self::WHITE[1], self::WHITE[2]);
        $this->SetFont('Helvetica', 'B', 16);
        $this->SetXY($left, $yBox + 3);
        $this->Cell($boxW, 10, $this->enc(number_format($totalWithVat, 2, ',', '.') . ' €'), 0, 0, 'L', false);
        $this->SetXY($left + $boxW + 10, $yBox + 3);
        $this->Cell($boxW, 10, $this->enc(number_format($totalWithoutVat, 2, ',', '.') . ' €'), 0, 0, 'L', false);
        $this->SetY($tableTop);

        // 5. Cabecera tabla: sin color de fondo, texto negro en negrita
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(self::BLACK[0], self::BLACK[1], self::BLACK[2]);
        $this->Cell($wFecha, $headerH, $this->enc('Fecha'), 0, 0, 'L');
        $this->Cell($wCol, $headerH, 'TPV', 0, 0, 'R');
        $this->Cell($wCol, $headerH, $this->enc('EFECTIVO'), 0, 0, 'R');
        $this->Cell($wCol, $headerH, $this->enc('TOTAL CON IVA'), 0, 0, 'R');
        $this->Cell($wCol, $headerH, $this->enc('TOTAL SIN IVA'), 0, 1, 'R');

        // 6. Filas de datos (altura calculada para ocupar justo una hoja)
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(self::BLACK[0], self::BLACK[1], self::BLACK[2]);
        foreach ($rows as $row) {
            $this->Cell($wFecha, $rowH, $this->enc($this->formatDate($row['date'])), 0, 0, 'L');
            $this->Cell($wCol, $rowH, $this->enc(number_format($row['bank_amount'], 2, ',', '.') . ' €'), 0, 0, 'R');
            $this->Cell($wCol, $rowH, $this->enc(number_format($row['cash_amount'], 2, ',', '.') . ' €'), 0, 0, 'R');
            $this->Cell($wCol, $rowH, $this->enc(number_format($row['total_with_vat'], 2, ',', '.') . ' €'), 0, 0, 'R');
            $this->Cell($wCol, $rowH, $this->enc(number_format($row['total_without_vat'], 2, ',', '.') . ' €'), 0, 1, 'R');
        }

        // 7. Fila TOTAL: sin color de fondo, texto negro en negrita
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(self::BLACK[0], self::BLACK[1], self::BLACK[2]);
        $this->Cell($wFecha, $totalRowH, $this->enc('TOTAL'), 0, 0, 'L');
        $this->Cell($wCol, $totalRowH, $this->enc(number_format($totalTpv, 2, ',', '.') . ' €'), 0, 0, 'R');
        $this->Cell($wCol, $totalRowH, $this->enc(number_format($totalEfectivo, 2, ',', '.') . ' €'), 0, 0, 'R');
        $this->Cell($wCol, $totalRowH, $this->enc(number_format($totalWithVat, 2, ',', '.') . ' €'), 0, 0, 'R');
        $this->Cell($wCol, $totalRowH, $this->enc(number_format($totalWithoutVat, 2, ',', '.') . ' €'), 0, 1, 'R');
    }
}
