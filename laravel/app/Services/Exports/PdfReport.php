<?php

declare(strict_types=1);

namespace App\Services\Exports;

final class PdfReport
{
    public function render(string $title, iterable $records): string
    {
        $pdf = new \FPDF('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->MultiCell(275, 8, $this->text(config('business.name', 'Studio & Progetto').' — '.$title));
        $pdf->SetFont('Helvetica', '', 9);
        $header = false;
        foreach ($records as $record) {
            $values = (array) $record;
            if (! $header) {
                $pdf->MultiCell(275, 6, $this->text(implode(' | ', array_keys($values))));
                $header = true;
            }
            $pdf->MultiCell(275, 5, $this->text(implode(' | ', array_map(fn ($value) => (string) $value, $values))));
            $pdf->Ln(2);
        }

        return $pdf->Output('S');
    }

    private function text(string $text): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT', $text) ?: '';
    }
}
