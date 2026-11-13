<?php

use App\Service\SecurityService;
use PHPUnit\Framework\TestCase;

final class SecurityServiceTest extends TestCase
{
    public function testCsrfAndRateLimitLogicUseExplicitValues(): void
    {
        $service = new SecurityService();

        self::assertTrue($service->isValidCsrfToken('token', 'token'));
        self::assertFalse($service->isValidCsrfToken('', 'token'));
        $entry = [];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $entry = $service->registerFailure($entry, 100, 5, 300);
        }

        self::assertSame(['intentos' => 0, 'bloqueado_hasta' => 400], $entry);
        self::assertTrue($service->isRateLimited($entry, 399));
        self::assertFalse($service->isRateLimited($entry, 400));
    }
}
