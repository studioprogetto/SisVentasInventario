<?php

declare(strict_types=1);

namespace App\Services\Exports;

use Illuminate\Support\Str;
use PharData;
use XMLWriter;

final class TabularExport
{
    public function workbook(iterable $records, string $title, string $period, string $caption, array $distribution): string
    {
        $name = storage_path('app/private/reporte-'.Str::uuid());
        $xml = new XMLWriter;
        $xml->openUri($name.'.xml');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'worksheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeRaw('<cols><col min="1" max="1" width="38" customWidth="1"/><col min="2" max="18" width="25" customWidth="1"/></cols>');
        $xml->startElement('sheetData');
        $this->row($xml, ['', config('business.name')]);
        $this->row($xml, ['', $title]);
        $this->row($xml, ['', $period]);
        $this->row($xml, [$caption]);
        foreach ($distribution as $label => $count) {
            $this->row($xml, [(string) $label, $count], ['grupo', 'cantidad']);
        }
        $this->row($xml, []);
        $header = false;
        foreach ($records as $record) {
            $row = (array) $record;
            if (! $header) {
                $this->row($xml, array_keys($row));
                $header = true;
            }
            $this->row($xml, array_values($row), array_keys($row));
        }
        if (! $header) $this->row($xml, ['Sin registros para esta consulta']);
        $xml->endElement();
        if ($distribution) {
            $end = 4 + count($distribution);
            $xml->writeRaw('<conditionalFormatting sqref="B5:B'.$end.'"><cfRule type="dataBar" priority="1"><dataBar><cfvo type="num" val="0"/><cfvo type="max"/><color rgb="FF267D8B"/></dataBar></cfRule></conditionalFormatting>');
        }
        $xml->writeRaw('<drawing xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:id="rId1"/>');
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
        $zip = new PharData($name.'.zip');
        $zip['[Content_Types].xml'] = '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/></Types>';
        $zip['_rels/.rels'] = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $zip['xl/workbook.xml'] = '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $zip['xl/_rels/workbook.xml.rels'] = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip['xl/styles.xml'] = '<?xml version="1.0"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><color rgb="FF1A2A44"/><name val="Poppins"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Poppins"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1A2A44"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>';
        $zip->addFile($name.'.xml', 'xl/worksheets/sheet1.xml');
        $zip->addFile(ReportPresentation::logo(), 'xl/media/logo.png');
        $rels = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $zip['xl/worksheets/_rels/sheet1.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="'.$rels.'/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
        $zip['xl/drawings/_rels/drawing1.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="'.$rels.'/image" Target="../media/logo.png"/></Relationships>';
        $zip['xl/drawings/drawing1.xml'] = '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="'.$rels.'">
            <xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>60000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>10000</xdr:rowOff></xdr:from><xdr:ext cx="480000" cy="480000"/>
            <xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Logo"/><xdr:cNvPicPr/></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
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
            if ($columns === []) {
                $xml->writeAttribute('s', '1');
            }
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
