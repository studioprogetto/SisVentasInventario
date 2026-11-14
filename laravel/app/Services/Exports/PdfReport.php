<?php

declare(strict_types=1);

namespace App\Services\Exports;

final class PdfReport
{
    public function render(string $title, iterable $records, string $period, string $caption, array $distribution): string
    {
        $pdf = new \FPDF('L', 'mm', 'A4');
        $pdf->SetTitle($this->text(config('business.name', 'Studio & Progetto').' - '.$title));
        $pdf->SetAuthor(config('business.name', 'Studio & Progetto'));
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();
        $pdf->SetFillColor(26, 42, 68);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetX(28);
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->Cell(0, 10, $this->text(config('business.name', 'Studio & Progetto')), 0, 1, 'L', true);
        $pdf->Image(ReportPresentation::logo(), 10, 12, 16, 16);
        $pdf->SetX(28);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 6, $this->text($title.' · '.$period.' | Generado: '.now()->format('d/m/Y H:i')), 0, 1, 'L', true);
        $pdf->Ln(5);
        $pdf->SetTextColor(26, 42, 68);
        if ($distribution) {
            $pdf->Cell(0, 7, $this->text($caption), 0, 1);
            $max = max(1, ...array_values($distribution));
            foreach ($distribution as $label => $count) {
                $pdf->Cell(66, 6, $this->text(mb_strimwidth((string) $label, 0, 36, '…')), 0, 0);
                $width = 90 * max(0, $count) / $max;
                $pdf->SetFillColor(38, 125, 139);
                $pdf->Rect($pdf->GetX(), $pdf->GetY()+1, $width, 4, 'F');
                $pdf->SetX($pdf->GetX()+$width+2);
                $pdf->Cell(20, 6, (string) $count, 0, 1);
            }
            $pdf->Ln(4);
        }
        $header = false;
        foreach ($records as $record) {
            $values = array_values((array) $record);
            $keys = array_keys((array) $record);
            if (! $header || $pdf->GetY() > 183) {
                if ($header) $pdf->AddPage();
                $this->tableHeading($pdf, $keys);
                $header = true;
            }
            $width = 277 / max(1, count($values));
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetTextColor(26, 42, 68);
            foreach ($values as $value) {
                $pdf->Cell($width, 6, $this->text(mb_strimwidth((string) ($value ?? 'No documentado'), 0, 34, '…')), 1, 0, 'L');
            }
            $pdf->Ln();
        }
        if (! $header) {
            $pdf->SetTextColor(96, 110, 130);
            $pdf->Cell(0, 8, 'No hay registros para el periodo seleccionado.', 0, 1);
        }
        return $pdf->Output('S');
    }

    private function tableHeading(\FPDF $pdf, array $keys): void
    {
        $pdf->SetFillColor(226, 234, 248);
        $pdf->SetTextColor(26, 42, 68);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $width = 277 / max(1, count($keys));
        foreach ($keys as $key) {
            $pdf->Cell($width, 7, $this->text(ucfirst(str_replace('_', ' ', (string) $key))), 1, 0, 'L', true);
        }
        $pdf->Ln();
    }

    private function text(string $text): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT', $text) ?: '';
    }
}
