<?php

namespace App\Services;

use ZipArchive;

class SimpleXlsxExportService
{
    public function make(array $report): string
    {
        $path = tempnam(sys_get_temp_dir(), 'audit-xlsx-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.htmlspecialchars(substr($report['title'], 0, 31), ENT_XML1).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($report));
        $zip->close();
        return $path;
    }

    private function sheet(array $report): string
    {
        $columnCount = count($report['columns']);
        $rows = [];
        $rows[] = $this->row(1, [$report['school']->name.' - '.$report['title']], 1);
        $rows[] = $this->row(2, ['Reporting period', ($report['from'] ?: 'Beginning').' to '.($report['to'] ?: 'Today')], 2);
        $rows[] = $this->row(3, ['Generated', $report['generatedAt']->format('Y-m-d H:i:s')], 2);
        $rows[] = $this->row(5, $report['columns'], 3);
        foreach ($report['rows'] as $index => $values) {
            $rows[] = $this->row($index + 6, array_values($values), 0, $report['currencyColumns']);
        }
        $last = count($report['rows']) + 5;
        $widths = collect($report['columns'])->map(fn ($h) => min(42, max(14, strlen($h) + 4)))->map(fn ($w, $i) => '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.$w.'" customWidth="1"/>')->implode('');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>'.$widths.'</cols><sheetData>'.implode('', $rows).'</sheetData><autoFilter ref="A5:'.$this->col($columnCount).'5"/><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
    }

    private function row(int $number, array $values, int $style, array $currencyColumns = []): string
    {
        $cells = '';
        foreach ($values as $index => $value) {
            $cellStyle = in_array($index, $currencyColumns, true) && is_numeric($value) ? 4 : $style;
            $ref = $this->col($index + 1).$number;
            $cells .= is_numeric($value) && $value !== '' ? '<c r="'.$ref.'" s="'.$cellStyle.'"><v>'.$value.'</v></c>' : '<c r="'.$ref.'" s="'.$cellStyle.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
        }
        return '<row r="'.$number.'">'.$cells.'</row>';
    }

    private function col(int $number): string { $name = ''; while ($number > 0) { $number--; $name = chr(65 + $number % 26).$name; $number = intdiv($number, 26); } return $name; }
    private function contentTypes(): string { return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>'; }
    private function rootRels(): string { return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>'; }
    private function styles(): string { return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="18"/><color rgb="FF172554"/><name val="Aptos Display"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF172554"/><bgColor indexed="64"/></patternFill></fill><borders count="2"><border/><border><bottom style="thin"><color rgb="FFD7DEE9"/></bottom></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>'; }
}
