<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Genera el PDF "Registro de ventas" con el diseño de la plantilla LT.
 * Una página por tienda: nombre, REGISTRO VENTAS [MES] [AÑO], totales, tabla por días, fila TOTAL.
 */
class DeclaredSalesRegistroPdf extends \FPDF
{
    /** Filas diarias agrupadas por store_id: [ store_id => [ [ date, bank_amount, ... ], ... ] ] */
    private array $rowsByStore;

    private string $companyName;

    private Carbon $monthStart;

    private array $spanMonthNames = [
        'enero' => 'enero', 'febrero' => 'febrero', 'marzo' => 'marzo', 'abril' => 'abril',
        'mayo' => 'mayo', 'junio' => 'junio', 'julio' => 'julio', 'agosto' => 'agosto',
        'septiembre' => 'septiembre', 'octubre' => 'octubre', 'noviembre' => 'noviembre', 'diciembre' => 'diciembre',
    ];

    private array $spanWeekdays = [
        'Monday' => 'lunes', 'Tuesday' => 'martes', 'Wednesday' => 'miércoles', 'Thursday' => 'jueves',
        'Friday' => 'viernes', 'Saturday' => 'sábado', 'Sunday' => 'domingo',
    ];

    public function __construct(array $dailyRows, string $companyName, Carbon $monthStart)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->companyName = $companyName;
        $this->monthStart = $monthStart->copy()->startOfMonth();
        $this->rowsByStore = $this->groupRowsByStore($dailyRows);
        $this->SetAutoPageBreak(true, 15);
        $this->SetMargins(15, 12, 15);
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

    /** Convierte UTF-8 a ISO-8859-1 para FPDF */
    private function l1(string $s): string
    {
        if ($s === '') {
            return '';
        }
        $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
        return $out !== false ? $out : $s;
    }

    private function monthNameFor(int $monthNum): string
    {
        $names = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $names[$monthNum] ?? '';
    }

    /** Formato de fecha como en la plantilla: "domingo, febrero 01, 2026" */
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
            $this->addStorePage($data['store_name'], $data['rows']);
        }
    }

    private function addStorePage(string $storeName, array $rows): void
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

        // Título: nombre tienda/empresa
        $this->SetFont('Helvetica', 'B', 12);
        $this->Cell(0, 6, $this->l1($storeName), 0, 1, 'L');

        // REGISTRO VENTAS [MES] [AÑO]
        $this->SetFont('Helvetica', 'B', 11);
        $titulo = 'REGISTRO VENTAS ' . strtoupper($this->monthNameFor((int) $this->monthStart->format('n'))) . ' ' . $this->monthStart->format('Y');
        $this->Cell(0, 6, $this->l1($titulo), 0, 1, 'L');

        // Total con IVA / Total sin IVA (una línea)
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(70, 6, $this->l1('Total con IVA'), 0, 0, 'L');
        $this->Cell(0, 6, number_format($totalWithVat, 2, ',', '.') . ' €', 0, 1, 'L');
        $this->Cell(70, 6, $this->l1('Total sin IVA'), 0, 0, 'L');
        $this->Cell(0, 6, number_format($totalWithoutVat, 2, ',', '.') . ' €', 0, 1, 'L');
        $this->Ln(3);

        // Cabecera tabla
        $this->SetFont('Helvetica', 'B', 9);
        $wFecha = 65;
        $wCol = 32;
        $this->Cell($wFecha, 6, $this->l1('Fecha'), 1, 0, 'L');
        $this->Cell($wCol, 6, 'TPV', 1, 0, 'R');
        $this->Cell($wCol, 6, $this->l1('EFECTIVO'), 1, 0, 'R');
        $this->Cell($wCol, 6, $this->l1('TOTAL CON IVA'), 1, 0, 'R');
        $this->Cell($wCol, 6, $this->l1('TOTAL SIN IVA'), 1, 1, 'R');

        $this->SetFont('Helvetica', '', 9);
        foreach ($rows as $row) {
            $this->Cell($wFecha, 5, $this->l1($this->formatDate($row['date'])), 1, 0, 'L');
            $this->Cell($wCol, 5, number_format($row['bank_amount'], 2, ',', '.') . ' €', 1, 0, 'R');
            $this->Cell($wCol, 5, number_format($row['cash_amount'], 2, ',', '.') . ' €', 1, 0, 'R');
            $this->Cell($wCol, 5, number_format($row['total_with_vat'], 2, ',', '.') . ' €', 1, 0, 'R');
            $this->Cell($wCol, 5, number_format($row['total_without_vat'], 2, ',', '.') . ' €', 1, 1, 'R');
        }

        // Fila TOTAL
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell($wFecha, 6, $this->l1('TOTAL'), 1, 0, 'L');
        $this->Cell($wCol, 6, number_format($totalTpv, 2, ',', '.') . ' €', 1, 0, 'R');
        $this->Cell($wCol, 6, number_format($totalEfectivo, 2, ',', '.') . ' €', 1, 0, 'R');
        $this->Cell($wCol, 6, number_format($totalWithVat, 2, ',', '.') . ' €', 1, 0, 'R');
        $this->Cell($wCol, 6, number_format($totalWithoutVat, 2, ',', '.') . ' €', 1, 1, 'R');
    }
}
