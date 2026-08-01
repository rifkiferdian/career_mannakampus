<?php

namespace Tests\Unit\Admin;

use App\Modules\Admin\Services\HrdSessionService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

final class HrdSessionServiceTest extends CIUnitTestCase
{
    public function testStableTokenSurvivesCodeIgniterSessionRegeneration(): void
    {
        $session = session();
        $session->remove('hrd_session_token');

        $service = new HrdSessionService($this->createMock(BaseConnection::class));
        $issueToken = new \ReflectionMethod($service, 'issueStableSessionHash');
        $currentHash = new \ReflectionMethod($service, 'currentSessionHash');

        $hashBeforeRegeneration = $issueToken->invoke($service);
        $token = $session->get('hrd_session_token');

        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame(hash('sha256', $token), $hashBeforeRegeneration);

        $session->regenerate(true);

        $this->assertSame($hashBeforeRegeneration, $currentHash->invoke($service));

        $service->clearCurrentToken();
        $this->assertFalse($session->has('hrd_session_token'));
    }
}
