<?php

namespace Tests\Unit\Admin;

use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use App\Modules\Admin\Services\ExcelWorkbookReader;
use CodeIgniter\Test\CIUnitTestCase;
use ZipArchive;

final class ExcelWorkbookReaderTest extends CIUnitTestCase
{
    public function testGeneratedTemplateCanBeReadForImport(): void
    {
        $headers = ['Nama Lengkap', 'NIK', 'Email'];
        $binary = (new ExcelWorkbookBuilder())->build(
            'Template Test',
            $headers,
            [['Budi Santoso', '3273010101010001', 'budi@example.com']],
            'Isi mulai baris 5.',
            [2],
        );
        $path = tempnam(WRITEPATH . 'cache', 'excel-reader-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, $binary);

        try {
            $workbook = (new ExcelWorkbookReader())->read($path);
        } finally {
            unlink($path);
        }

        $this->assertSame($headers, $workbook['headers']);
        $this->assertSame(5, $workbook['rows'][0]['row_number']);
        $this->assertSame(['Budi Santoso', '3273010101010001', 'budi@example.com'], $workbook['rows'][0]['values']);
    }

    public function testWorkbookUsingSharedStringsCanBeRead(): void
    {
        $path = tempnam(WRITEPATH . 'cache', 'excel-shared-test-');
        $this->assertNotFalse($path);
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $archive->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Nama Lengkap</t></si><si><t>NIK</t></si><si><t>Siti Aminah</t></si><si><t>3273010101010002</t></si></sst>');
        $archive->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="4"><c r="A4" t="s"><v>0</v></c><c r="B4" t="s"><v>1</v></c></row><row r="5"><c r="A5" t="s"><v>2</v></c><c r="B5" t="s"><v>3</v></c></row></sheetData></worksheet>');
        $archive->close();

        try {
            $workbook = (new ExcelWorkbookReader())->read($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(['Nama Lengkap', 'NIK'], $workbook['headers']);
        $this->assertSame(['Siti Aminah', '3273010101010002'], $workbook['rows'][0]['values']);
    }
}
