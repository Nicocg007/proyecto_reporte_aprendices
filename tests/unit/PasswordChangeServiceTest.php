<?php

use App\Service\PasswordChangeService;
use PHPUnit\Framework\TestCase;

final class PasswordChangeServiceTest extends TestCase
{
    public function testPasswordPairAndCodeValidationPreserveFeedbackStates(): void
    {
        $service = new PasswordChangeService();

        self::assertSame('mismatch', $service->validatePasswordPair('secret1', 'secret2'));
        self::assertSame('short', $service->validatePasswordPair('short', 'short'));
        self::assertNull($service->validatePasswordPair('secret1', 'secret1'));
        self::assertSame('invalid', $service->validateCode('wrong', '123456', 200, 100));
        self::assertSame('expired', $service->validateCode('123456', '123456', 100, 101));
        self::assertNull($service->validateCode('123456', '123456', 101, 101));
        self::assertSame(0, $service->secondsRemaining(90, 100));
    }
}
