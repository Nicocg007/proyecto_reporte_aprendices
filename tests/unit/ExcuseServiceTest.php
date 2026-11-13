<?php

use App\Service\ExcuseService;
use PHPUnit\Framework\TestCase;

final class ExcuseServiceTest extends TestCase
{
    public function testSubmissionRequiresDateAndReason(): void
    {
        $service = new ExcuseService();

        self::assertSame('campos', $service->validateSubmission('', 'motivo'));
        self::assertNull($service->validateSubmission('2026-11-13', 'motivo'));
    }

    public function testAttachmentRequiresAllowedExtensionSizeAndMime(): void
    {
        $service = new ExcuseService();

        self::assertNull($service->validateAttachment('pdf', 100, 'application/pdf'));
        self::assertSame('archivo', $service->validateAttachment('php', 100, 'application/pdf'));
        self::assertSame('tamano', $service->validateAttachment('pdf', 5 * 1024 * 1024 + 1, 'application/pdf'));
        self::assertSame('archivo', $service->validateAttachment('jpg', 100, 'application/pdf'));
    }
}
