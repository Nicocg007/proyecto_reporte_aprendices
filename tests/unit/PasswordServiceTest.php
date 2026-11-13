<?php

use App\Service\PasswordService;
use PHPUnit\Framework\TestCase;

final class PasswordServiceTest extends TestCase
{
    public function testHashedPasswordVerifiesOnlyTheOriginalPassword(): void
    {
        $service = new PasswordService();
        $hash = $service->hash('correct-horse');

        self::assertNotSame('correct-horse', $hash);
        self::assertTrue($service->verify('correct-horse', $hash));
        self::assertFalse($service->verify('incorrect-horse', $hash));
    }
}
