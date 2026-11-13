<?php

use App\Service\PasswordService;
use PHPUnit\Framework\TestCase;

final class PasswordServiceTest extends TestCase
{
    public function testHashIsDifferentFromTheOriginalPassword(): void
    {
        $service = new PasswordService();
        $hash = $service->hash('correct-horse');

        self::assertNotSame('correct-horse', $hash);
    }

    public function testHashVerifiesTheOriginalPassword(): void
    {
        $service = new PasswordService();
        $hash = $service->hash('correct-horse');

        self::assertTrue($service->verify('correct-horse', $hash));
    }

    public function testHashRejectsAnIncorrectPassword(): void
    {
        $service = new PasswordService();
        $hash = $service->hash('correct-horse');

        self::assertFalse($service->verify('incorrect-horse', $hash));
    }

    public function testEmptyPasswordCanBeHashedAndVerified(): void
    {
        $service = new PasswordService();
        $hash = $service->hash('');

        self::assertTrue($service->verify('', $hash));
    }

    public function testSamePasswordGetsDifferentSaltsAndBothHashesVerify(): void
    {
        $service = new PasswordService();
        $firstHash = $service->hash('correct-horse');
        $secondHash = $service->hash('correct-horse');

        self::assertNotSame($firstHash, $secondHash);
        self::assertTrue($service->verify('correct-horse', $firstHash));
        self::assertTrue($service->verify('correct-horse', $secondHash));
    }
}
