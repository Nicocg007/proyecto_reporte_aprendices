<?php

use App\Service\SecurityService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SecurityServiceTest extends TestCase
{
    #[DataProvider('csrfCases')]
    public function testCsrfTokenValidation(string $expected, string $provided, bool $isValid): void
    {
        self::assertSame(
            $isValid,
            (new SecurityService())->isValidCsrfToken($expected, $provided)
        );
    }

    public static function csrfCases(): array
    {
        return [
            'identical nonempty tokens are valid' => ['token', 'token', true],
            'empty expected token is invalid' => ['', 'token', false],
            'different tokens are invalid' => ['token', 'other', false],
        ];
    }

    public function testRateLimitAndEscapingUseExplicitValues(): void
    {
        $service = new SecurityService();
        $entry = [];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $entry = $service->registerFailure($entry, 100, 5, 300);
        }

        self::assertSame(['intentos' => 0, 'bloqueado_hasta' => 400], $entry);
        self::assertTrue($service->isRateLimited($entry, 399));
        self::assertFalse($service->isRateLimited($entry, 400));
        self::assertSame('&lt;tag attr=&quot;x&quot;&gt;&amp;&lt;/tag&gt;', $service->escape('<tag attr="x">&</tag>'));
    }
}
