<?php

namespace Tests\Unit\Admin;

use App\Modules\Admin\Services\ExcelWorkbookBuilder;
use CodeIgniter\Test\CIUnitTestCase;
use ZipArchive;

/** @internal */
final class ExcelWorkbookBuilderTest extends CIUnitTestCase
{
    public function testBuildsValidExcelWorkbookWithInlineTextCells(): void
    {
        $binary = (new ExcelWorkbookBuilder())->build(
            'Laporan Pelamar',
            ['Nama', 'Email'],
            [['Budi & Sari', '=bukan formula']],
        );
        $path = tempnam(WRITEPATH . 'cache', 'xlsx-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, $binary);

        $archive = new ZipArchive();
        try {
            $this->assertTrue($archive->open($path) === true);
            $this->assertNotFalse($archive->locateName('xl/workbook.xml'));
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            $this->assertIsString($worksheet);
            $this->assertStringContainsString('Budi &amp; Sari', $worksheet);
            $this->assertStringContainsString('=bukan formula', $worksheet);
            $this->assertStringNotContainsString('<f>', $worksheet);
        } finally {
            $archive->close();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
