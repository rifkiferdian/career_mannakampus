<?php

namespace Tests\Unit\Recruitment;

use App\Libraries\StorageSyncSignature;
use CodeIgniter\Test\CIUnitTestCase;

final class StorageSyncSignatureTest extends CIUnitTestCase
{
    public function testCanonicalRequestIsStable(): void
    {
        $canonical = StorageSyncSignature::canonical(
            'manna-local-01',
            'post',
            '/api/storage/documents/10/confirm',
            '1788242400',
            '0123456789abcdef0123456789abcdef',
            '{"file_size":100}',
        );

        $this->assertSame(6, count(explode("\n", $canonical)));
        $this->assertStringContainsString("\nPOST\n/api/storage/documents/10/confirm\n", $canonical);
        $this->assertStringEndsWith(hash('sha256', '{"file_size":100}'), $canonical);
    }

    public function testSignatureChangesWhenRequestIsTampered(): void
    {
        $arguments = [
            'secret-with-at-least-thirty-two-characters',
            'manna-local-01',
            'POST',
            '/api/storage/documents/10/confirm',
            '1788242400',
            '0123456789abcdef0123456789abcdef',
        ];

        $original = StorageSyncSignature::sign(...[...$arguments, '{"file_size":100}']);
        $tampered = StorageSyncSignature::sign(...[...$arguments, '{"file_size":101}']);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $original);
        $this->assertNotSame($original, $tampered);
    }
}
