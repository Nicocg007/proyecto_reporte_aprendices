<?php

use App\Service\ExcuseService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExcuseServiceTest extends TestCase
{
    #[DataProvider('submissionCases')]
    public function testSubmissionValidation(string $date, string $reason, ?string $expected): void
    {
        self::assertSame($expected, (new ExcuseService())->validateSubmission($date, $reason));
    }

    public static function submissionCases(): array
    {
        return [
            'date and reason provided' => ['2026-11-13', 'Consulta medica', null],
            'missing date is rejected' => ['', 'Consulta medica', 'campos'],
        ];
    }

    #[DataProvider('attachmentCases')]
    public function testAttachmentValidation(
        string $extension,
        int $size,
        string $mime,
        ?string $expected
    ): void {
        self::assertSame(
            $expected,
            (new ExcuseService())->validateAttachment($extension, $size, $mime)
        );
    }

    public static function attachmentCases(): array
    {
        return [
            'case-insensitive pdf at the size limit is accepted' => [
                'PDF',
                5 * 1024 * 1024,
                'application/pdf',
                null,
            ],
            'unsupported extension is rejected' => [
                'php',
                100,
                'application/pdf',
                'archivo',
            ],
            'size above limit is rejected' => [
                'pdf',
                5 * 1024 * 1024 + 1,
                'application/pdf',
                'tamano',
            ],
            'mime must match the extension' => [
                'jpg',
                100,
                'application/pdf',
                'archivo',
            ],
        ];
    }

    #[DataProvider('notificationRoleCases')]
    public function testNotificationsAreScopedByRole(array $counts, string $role, array $expected): void
    {
        self::assertSame($expected, (new ExcuseService())->notifications($counts, $role));
    }

    public static function notificationRoleCases(): array
    {
        return [
            'administrator sees pending review count' => [
                ['Pendiente' => 2],
                'Administrador',
                [[
                    'texto' => 'Tienes 2 excusa(s) pendiente(s) por revisar',
                    'url' => 'admin_excusas.php',
                ]],
            ],
            'instructor sees pending review count' => [
                ['Pendiente' => 2],
                'Instructor',
                [[
                    'texto' => 'Tienes 2 excusa(s) pendiente(s) por revisar',
                    'url' => 'instructor_excusas.php',
                ]],
            ],
            'learner sees each nonzero status count' => [
                ['Pendiente' => 1, 'Aprobada' => 2, 'Rechazada' => 3],
                'Aprendiz',
                [
                    [
                        'texto' => 'Tienes 1 excusa(s) en espera de revision',
                        'url' => 'aprendiz_excusas.php',
                    ],
                    [
                        'texto' => '2 excusa(s) aprobada(s)',
                        'url' => 'aprendiz_excusas.php',
                    ],
                    [
                        'texto' => '3 excusa(s) rechazada(s)',
                        'url' => 'aprendiz_excusas.php',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('zeroNotificationCases')]
    public function testNotificationsOmitZeroCounts(array $counts, string $role): void
    {
        self::assertSame([], (new ExcuseService())->notifications($counts, $role));
    }

    public static function zeroNotificationCases(): array
    {
        return [
            'administrator omits zero pending count' => [['Pendiente' => 0], 'Administrador'],
            'instructor omits zero pending count' => [['Pendiente' => 0], 'Instructor'],
            'learner omits zero status counts' => [
                ['Pendiente' => 0, 'Aprobada' => 0, 'Rechazada' => 0],
                'Aprendiz',
            ],
        ];
    }
}
