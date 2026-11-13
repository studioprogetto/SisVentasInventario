<?php

declare(strict_types=1);

namespace App\Services\Exports;

use Illuminate\Support\Str;
use PharData;
use XMLWriter;

final class TabularExport
{
    public function workbook(iterable $records): string
    {
        $name = storage_path('app/private/reporte-'.Str::uuid());
        $xml = new XMLWriter;
        $xml->openUri($name.'.xml');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'worksheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->startElement('sheetData');
        $header = false;
        foreach ($records as $record) {
            $row = (array) $record;
            if (! $header) {
                $this->row($xml, array_keys($row));
                $header = true;
            }
            $this->row($xml, array_values($row), array_keys($row));
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
        $zip = new PharData($name.'.zip');
        $zip['[Content_Types].xml'] = '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        $zip['_rels/.rels'] = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $zip['xl/workbook.xml'] = '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $zip['xl/_rels/workbook.xml.rels'] = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
        $zip->addFile($name.'.xml', 'xl/worksheets/sheet1.xml');
        unset($zip);
        unlink($name.'.xml');
        rename($name.'.zip', $name.'.xlsx');

        return $name.'.xlsx';
    }

    private function row(XMLWriter $xml, array $values, array $columns = []): void
    {
        $xml->startElement('row');
        foreach ($values as $index => $value) {
            $xml->startElement('c');
            if (is_numeric($value) && in_array($columns[$index] ?? '', ['importe', 'stock', 'minimo', 'costo_actual', 'valor_actual', 'cantidad', 'antes', 'despues', 'operaciones', 'saldo_utilizado', 'cobro_base', 'unidades_netas', 'ganancia_base_documentada', 'esperado', 'declarado', 'diferencia', 'inicial', 'compras', 'sellos'], true)) {
                $xml->writeElement('v', (string) $value);
                $xml->endElement();

                continue;
            }
            $xml->writeAttribute('t', 'inlineStr');
            $xml->startElement('is');
            $xml->writeElement('t', preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $value));
            $xml->endElement();
            $xml->endElement();
        }
        $xml->endElement();
    }
}
