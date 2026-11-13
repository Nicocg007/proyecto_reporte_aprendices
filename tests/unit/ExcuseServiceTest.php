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

    public function testNotificationsAreScopedAndOmitZeroCounts(): void
    {
        $service = new ExcuseService();

        self::assertSame(
            [[
                'texto' => 'Tienes 2 excusa(s) pendiente(s) por revisar',
                'url' => 'admin_excusas.php',
            ]],
            $service->notifications(['Pendiente' => 2], 'Administrador')
        );
        self::assertSame([], $service->notifications(['Pendiente' => 0], 'Instructor'));
        self::assertSame([
            [
                'texto' => 'Tienes 1 excusa(s) en espera de revision',
                'url' => 'aprendiz_excusas.php',
            ],
            [
                'texto' => '2 excusa(s) aprobada(s)',
                'url' => 'aprendiz_excusas.php',
            ],
        ], $service->notifications(['Pendiente' => 1, 'Aprobada' => 2], 'Aprendiz'));
    }
}
