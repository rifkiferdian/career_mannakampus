<?php

namespace App\Modules\Admin\Services;

use RuntimeException;
use ZipArchive;

class ExcelWorkbookBuilder
{
    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     * @param list<int> $textColumns One-based column numbers that must remain text in Excel.
     */
    public function build(string $title, array $headers, array $rows, ?string $subtitle = null, array $textColumns = []): string
    {
        $temporaryPath = tempnam(WRITEPATH . 'cache', 'applicant-report-');
        if ($temporaryPath === false) {
            throw new RuntimeException('File Excel sementara tidak dapat dibuat.');
        }

        $archive = new ZipArchive();
        $archiveIsOpen = false;
        try {
            if ($archive->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Workbook Excel tidak dapat dibuat.');
            }
            $archiveIsOpen = true;

            $archive->addFromString('[Content_Types].xml', $this->contentTypes());
            $archive->addFromString('_rels/.rels', $this->rootRelationships());
            $archive->addFromString('docProps/app.xml', $this->appProperties());
            $archive->addFromString('docProps/core.xml', $this->coreProperties($title));
            $archive->addFromString('xl/workbook.xml', $this->workbook($title));
            $archive->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
            $archive->addFromString('xl/styles.xml', $this->styles());
            $archive->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($title, $headers, $rows, $subtitle, $textColumns));
            if (! $archive->close()) {
                throw new RuntimeException('Workbook Excel tidak dapat diselesaikan.');
            }
            $archiveIsOpen = false;

            $binary = file_get_contents($temporaryPath);
            if ($binary === false) {
                throw new RuntimeException('Workbook Excel tidak dapat dibaca.');
            }

            return $binary;
        } finally {
            if ($archiveIsOpen) {
                $archive->close();
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /** @param list<int> $textColumns */
    private function worksheet(string $title, array $headers, array $rows, ?string $subtitle, array $textColumns): string
    {
        $lastColumn = $this->columnName(count($headers));
        $lastRow = count($rows) + 4;
        $sheetRows = $this->rowXml(1, [$title], 1)
            . $this->rowXml(2, [$subtitle ?? 'Diekspor pada ' . date('d/m/Y H:i')], 0)
            . $this->rowXml(4, $headers, 2);

        foreach ($rows as $index => $row) {
            $sheetRows .= $this->rowXml($index + 5, $row, 0);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastColumn . $lastRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . $this->columnsXml(count($headers), $textColumns)
            . '<sheetData>' . $sheetRows . '</sheetData>'
            . '<autoFilter ref="A4:' . $lastColumn . $lastRow . '"/>'
            . '<mergeCells count="2"><mergeCell ref="A1:' . $lastColumn . '1"/><mergeCell ref="A2:' . $lastColumn . '2"/></mergeCells>'
            . '</worksheet>';
    }

    /** @param list<int> $textColumns */
    private function columnsXml(int $count, array $textColumns): string
    {
        $xml = '<cols>';
        for ($column = 1; $column <= $count; $column++) {
            $width = match ($column) {
                1 => 24, 2 => 22, 3 => 31, 4 => 20, 5, 6 => 28, default => 22,
            };
            $style = in_array($column, $textColumns, true) ? ' style="3"' : '';
            $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"' . $style . '/>';
        }

        return $xml . '</cols>';
    }

    /** @param list<string> $cells */
    private function rowXml(int $rowNumber, array $cells, int $style): string
    {
        $xml = '<row r="' . $rowNumber . '"' . ($rowNumber === 1 ? ' ht="28" customHeight="1"' : '') . '>';
        foreach ($cells as $index => $value) {
            $reference = $this->columnName($index + 1) . $rowNumber;
            $xml .= '<c r="' . $reference . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . $this->escape((string) $value) . '</t></is></c>';
        }

        return $xml . '</row>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return htmlspecialchars(mb_substr($value, 0, 32767), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbook(string $title): string
    {
        $sheetName = mb_substr(preg_replace('~[\\/?*\[\]:]+~u', ' ', $title) ?: 'Data', 0, 31);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $this->escape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF102A43"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF87638"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE4EA"/></left><right style="thin"><color rgb="FFDDE4EA"/></right><top style="thin"><color rgb="FFDDE4EA"/></top><bottom style="thin"><color rgb="FFDDE4EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Manna Kampus Career</Application></Properties>';
    }

    private function coreProperties(string $title): string
    {
        $createdAt = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>' . $this->escape($title) . '</dc:title><dc:creator>HRD Manna Kampus</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . $createdAt . '</dcterms:created></cp:coreProperties>';
    }
}
