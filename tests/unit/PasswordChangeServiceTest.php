<?php

use App\Service\PasswordChangeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PasswordChangeServiceTest extends TestCase
{
    #[DataProvider('passwordPairCases')]
    public function testPasswordPairValidation(string $password, string $confirmation, ?string $expected): void
    {
        self::assertSame(
            $expected,
            (new PasswordChangeService())->validatePasswordPair($password, $confirmation)
        );
    }

    public static function passwordPairCases(): array
    {
        return [
            'matching password at minimum length is accepted' => ['secret', 'secret', null],
            'different confirmation is rejected' => ['secret1', 'secret2', 'mismatch'],
            'matching password below minimum length is rejected' => ['short', 'short', 'short'],
        ];
    }

    #[DataProvider('codeCases')]
    public function testCodeValidationAndRemainingTime(
        string $provided,
        string $expectedCode,
        int $expiration,
        int $now,
        ?string $expectedError,
        int $expectedRemaining
    ): void {
        $service = new PasswordChangeService();

        self::assertSame(
            $expectedError,
            $service->validateCode($provided, $expectedCode, $expiration, $now)
        );
        self::assertSame($expectedRemaining, $service->secondsRemaining($expiration, $now));
    }

    public static function codeCases(): array
    {
        return [
            'matching code at expiration boundary remains valid' => [
                '123456',
                '123456',
                100,
                100,
                null,
                0,
            ],
            'different code is invalid while unexpired' => [
                'wrong',
                '123456',
                200,
                100,
                'invalid',
                100,
            ],
            'matching code after expiration is rejected' => [
                '123456',
                '123456',
                100,
                101,
                'expired',
                0,
            ],
        ];
    }
}
