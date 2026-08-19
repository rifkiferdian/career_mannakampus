<?php

namespace Tests\Unit\Recruitment;

use App\Modules\Recruitment\Services\ApplicationReceiptPdf;
use PHPUnit\Framework\TestCase;

class ApplicationReceiptPdfTest extends TestCase
{
    public function testItGeneratesReceiptWithoutScreeningResult(): void
    {
        $pdf = (new ApplicationReceiptPdf())->generate([
            'batch_number' => 'MKB-260819-ABC12345',
            'submitted_at' => '19/08/2026 10:00',
            'profile' => [
                'full_name' => 'Budi Santoso',
                'email' => 'budi@example.test',
                'phone' => '081234567890',
                'birth_place' => 'Sleman',
                'birth_date' => '1998-05-15',
                'last_education' => 'S1',
                'institution' => 'Universitas Contoh',
                'major' => 'Teknik Informatika',
            ],
            'applications' => [[
                'title' => 'Programmer',
                'application_number' => 'MK-260819-12345678',
                'preference_order' => 1,
            ]],
        ]);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('MKB-260819-ABC12345', $pdf);
        $this->assertStringContainsString('Budi Santoso', $pdf);
        $this->assertStringContainsString('MK-260819-12345678', $pdf);
        $this->assertStringNotContainsString('screening', strtolower($pdf));
        $this->assertStringContainsString('%%EOF', $pdf);
    }
}
