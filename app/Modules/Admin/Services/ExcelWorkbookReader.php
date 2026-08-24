<?php

namespace App\Modules\Admin\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ExcelWorkbookReader
{
    private const MAX_UNCOMPRESSED_BYTES = 20_000_000;

    /**
     * @return array{headers: list<string>, rows: list<array{row_number: int, values: list<string>}>}
     */
    public function read(string $path, int $headerRow = 4, int $maximumRows = 1000): array
    {
        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            throw new RuntimeException('File bukan workbook Excel .xlsx yang valid.');
        }

        try {
            $this->assertSafeArchive($archive);
            $worksheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
            if ($worksheetXml === false) {
                throw new RuntimeException('Sheet pertama tidak ditemukan dalam file Excel.');
            }
            $sharedStrings = $this->sharedStrings($archive);
        } finally {
            $archive->close();
        }

        $worksheet = $this->xml($worksheetXml, 'Worksheet Excel tidak dapat dibaca.');
        $worksheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = $worksheet->xpath('//x:sheetData/x:row') ?: [];
        $headers = [];
        $result = [];
        foreach ($rows as $row) {
            $rowNumber = (int) $row['r'];
            if ($rowNumber < $headerRow) {
                continue;
            }
            $values = $this->rowValues($row, $sharedStrings);
            if ($rowNumber === $headerRow) {
                $headers = $values;
                continue;
            }
            if (count($result) >= $maximumRows) {
                throw new RuntimeException('Maksimal ' . $maximumRows . ' baris data per file.');
            }
            if (array_filter($values, static fn (string $value): bool => trim($value) !== '') !== []) {
                $result[] = ['row_number' => $rowNumber, 'values' => $values];
            }
        }
        if ($headers === []) {
            throw new RuntimeException('Header template pada baris 4 tidak ditemukan.');
        }

        return ['headers' => $headers, 'rows' => $result];
    }

    private function assertSafeArchive(ZipArchive $archive): void
    {
        $size = 0;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index);
            if (! is_array($stat)) {
                throw new RuntimeException('Struktur file Excel tidak valid.');
            }
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
            if ($name === '' || str_starts_with($name, '/') || str_contains($name, '../')) {
                throw new RuntimeException('Struktur file Excel tidak aman.');
            }
            $size += (int) ($stat['size'] ?? 0);
            if ($size > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('Isi file Excel terlalu besar.');
            }
        }
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $archive): array
    {
        $xml = $archive->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $document = $this->xml($xml, 'Daftar teks Excel tidak dapat dibaca.');
        $document->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];
        foreach ($document->xpath('//x:si') ?: [] as $item) {
            $item->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = $item->xpath('.//x:t') ?: [];
            $strings[] = implode('', array_map(static fn (SimpleXMLElement $part): string => (string) $part, $parts));
        }

        return $strings;
    }

    /** @param list<string> $sharedStrings
     *  @return list<string>
     */
    private function rowValues(SimpleXMLElement $row, array $sharedStrings): array
    {
        $row->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $values = [];
        foreach ($row->xpath('./x:c') ?: [] as $cell) {
            $reference = (string) $cell['r'];
            preg_match('/\A([A-Z]+)/', $reference, $matches);
            $index = $this->columnNumber($matches[1] ?? '') - 1;
            if ($index < 0) {
                continue;
            }
            while (count($values) < $index) {
                $values[] = '';
            }
            $type = (string) $cell['t'];
            $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            if ($type === 'inlineStr') {
                $parts = $cell->xpath('.//x:is//x:t') ?: [];
                $value = implode('', array_map(static fn (SimpleXMLElement $part): string => (string) $part, $parts));
            } else {
                $raw = (string) (($cell->xpath('./x:v') ?: [])[0] ?? '');
                $value = $type === 's' ? ($sharedStrings[(int) $raw] ?? '') : $raw;
            }
            $values[$index] = trim($value);
        }

        return $values;
    }

    private function columnNumber(string $name): int
    {
        $number = 0;
        foreach (str_split($name) as $character) {
            $number = ($number * 26) + ord($character) - 64;
        }

        return $number;
    }

    private function xml(string $xml, string $message): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if ($document === false) {
                throw new RuntimeException($message);
            }

            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
