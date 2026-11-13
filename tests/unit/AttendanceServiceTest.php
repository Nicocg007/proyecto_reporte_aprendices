<?php

use App\Service\AttendanceService;
use PHPUnit\Framework\TestCase;

final class AttendanceServiceTest extends TestCase
{
    public function testAllowedStatusesMatchTheAttendanceFormOptions(): void
    {
        self::assertSame(
            ['Normal', 'Retardo', 'Inasistencia', 'Salida Temprana'],
            (new AttendanceService())->allowedStatuses()
        );
    }

    public function testCheckInMarksLateAndRoundsMinutes(): void
    {
        $result = (new AttendanceService())->calculateCheckIn('08:01:31', '08:00:00');

        self::assertSame(['estado' => 'Retardo', 'minutos_retardo' => 2], $result);
    }

    public function testManualAttendanceNormalizesUnknownStatusAndLateMinutes(): void
    {
        $service = new AttendanceService();

        self::assertSame(
            ['estado' => 'Inasistencia', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            $service->calculateManualAttendance('08:20', 'unknown', '08:00')
        );
        self::assertSame(
            ['estado' => 'Retardo', 'minutos_retardo' => 20, 'salida_temprana' => 0],
            $service->calculateManualAttendance('08:20', 'Retardo', '08:00')
        );
    }

    public function testReportFiltersDropInvalidDateAndStatus(): void
    {
        $filters = (new AttendanceService())->normalizeReportFilters([
            'fecha_inicio' => '2026-02-31',
            'fecha_fin' => '2026-02-01',
            'ficha' => '4',
            'estado' => 'Inventado',
        ]);

        self::assertSame([
            'fecha_inicio' => '2026-02-31',
            'fecha_fin' => '2026-02-01',
            'ficha' => '4',
            'estado' => '',
        ], $filters);
    }
}
