<?php

use App\Service\AttendanceService;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('checkInCases')]
    public function testCalculateCheckIn(string $currentTime, ?string $scheduledTime, array $expected): void
    {
        self::assertSame(
            $expected,
            (new AttendanceService())->calculateCheckIn($currentTime, $scheduledTime)
        );
    }

    public static function checkInCases(): array
    {
        return [
            'exactly on schedule' => [
                '08:00:00',
                '08:00:00',
                ['estado' => 'Normal', 'minutos_retardo' => 0],
            ],
            'before schedule' => [
                '07:59:59',
                '08:00:00',
                ['estado' => 'Normal', 'minutos_retardo' => 0],
            ],
            'late by less than thirty seconds rounds to zero' => [
                '08:00:29',
                '08:00:00',
                ['estado' => 'Retardo', 'minutos_retardo' => 0],
            ],
            'half-minute rounds up' => [
                '08:00:30',
                '08:00:00',
                ['estado' => 'Retardo', 'minutos_retardo' => 1],
            ],
            'rounds a minute and a half to two' => [
                '08:01:31',
                '08:00:00',
                ['estado' => 'Retardo', 'minutos_retardo' => 2],
            ],
            'missing schedule does not mark a delay' => [
                '08:30:00',
                null,
                ['estado' => 'Normal', 'minutos_retardo' => 0],
            ],
            'empty time with no schedule remains normal' => [
                '',
                null,
                ['estado' => 'Normal', 'minutos_retardo' => 0],
            ],
        ];
    }

    #[DataProvider('manualAttendanceCases')]
    public function testCalculateManualAttendance(
        ?string $checkIn,
        string $status,
        ?string $scheduledTime,
        array $expected
    ): void {
        self::assertSame(
            $expected,
            (new AttendanceService())->calculateManualAttendance($checkIn, $status, $scheduledTime)
        );
    }

    public static function manualAttendanceCases(): array
    {
        return [
            'normal attendance on time' => [
                '08:00',
                'Normal',
                '08:00:00',
                ['estado' => 'Normal', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            ],
            'early check-in has no negative delay' => [
                '07:45',
                'Normal',
                '08:00:00',
                ['estado' => 'Normal', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            ],
            'late attendance calculates whole minutes' => [
                '08:23',
                'Retardo',
                '08:00:00',
                ['estado' => 'Retardo', 'minutos_retardo' => 23, 'salida_temprana' => 0],
            ],
            'early departure sets its flag' => [
                '08:05',
                'Salida Temprana',
                '08:00:00',
                ['estado' => 'Salida Temprana', 'minutos_retardo' => 5, 'salida_temprana' => 1],
            ],
            'absence overrides provided check-in' => [
                '08:30',
                'Inasistencia',
                '08:00:00',
                ['estado' => 'Inasistencia', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            ],
            'unknown status becomes absence' => [
                '08:30',
                'unknown',
                '08:00:00',
                ['estado' => 'Inasistencia', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            ],
            'empty check-in has no delay' => [
                null,
                'Retardo',
                '08:00:00',
                ['estado' => 'Retardo', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            ],
            'empty string check-in has no delay' => [
                '',
                'Normal',
                '08:00:00',
                ['estado' => 'Normal', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            ],
            'missing schedule has no delay' => [
                '08:30',
                'Retardo',
                null,
                ['estado' => 'Retardo', 'minutos_retardo' => 0, 'salida_temprana' => 0],
            ],
        ];
    }

    #[DataProvider('reportFilterCases')]
    public function testNormalizeReportFilters(array $input, array $expected): void
    {
        self::assertSame($expected, (new AttendanceService())->normalizeReportFilters($input));
    }

    public static function reportFilterCases(): array
    {
        return [
            'missing inputs become empty filters' => [
                [],
                ['fecha_inicio' => '', 'fecha_fin' => '', 'ficha' => '', 'estado' => ''],
            ],
            'trims date and accepts known state' => [
                [
                    'fecha_inicio' => ' 2026-11-13 ',
                    'fecha_fin' => '2026-11-14',
                    'ficha' => '4',
                    'estado' => ' Retardo ',
                ],
                [
                    'fecha_inicio' => '2026-11-13',
                    'fecha_fin' => '2026-11-14',
                    'ficha' => '4',
                    'estado' => 'Retardo',
                ],
            ],
            'rejects date with wrong format and unknown state' => [
                [
                    'fecha_inicio' => '2026-2-03',
                    'fecha_fin' => 'not-a-date',
                    'ficha' => '',
                    'estado' => 'retardo',
                ],
                ['fecha_inicio' => '', 'fecha_fin' => '', 'ficha' => '', 'estado' => ''],
            ],
        ];
    }

    #[DataProvider('statusCountCases')]
    public function testCountByStatus(array $rows, array $expected): void
    {
        self::assertSame($expected, (new AttendanceService())->countByStatus($rows));
    }

    public static function statusCountCases(): array
    {
        $emptyCounts = [
            'Normal' => 0,
            'Retardo' => 0,
            'Inasistencia' => 0,
            'Salida Temprana' => 0,
        ];

        return [
            'empty rows initialize all statuses' => [
                [],
                $emptyCounts,
            ],
            'counts known states and ignores unknown state' => [
                [
                    ['estado' => 'Normal'],
                    ['estado' => 'Retardo'],
                    ['estado' => 'Retardo'],
                    ['estado' => 'Inasistencia'],
                    ['estado' => 'Salida Temprana'],
                    ['estado' => 'Otro'],
                ],
                [
                    'Normal' => 1,
                    'Retardo' => 2,
                    'Inasistencia' => 1,
                    'Salida Temprana' => 1,
                ],
            ],
        ];
    }

    #[DataProvider('paginationCases')]
    public function testNormalizePagination(int $page, int $perPage, int $totalRows, array $expected): void
    {
        self::assertSame(
            $expected,
            (new AttendanceService())->normalizePagination($page, $perPage, $totalRows)
        );
    }

    public static function paginationCases(): array
    {
        return [
            'middle page' => [2, 10, 25, ['page' => 2, 'total_pages' => 3, 'offset' => 10]],
            'page below first is clamped' => [0, 10, 25, ['page' => 1, 'total_pages' => 3, 'offset' => 0]],
            'page above last is clamped' => [8, 10, 25, ['page' => 3, 'total_pages' => 3, 'offset' => 20]],
            'no rows still has one page' => [4, 10, 0, ['page' => 1, 'total_pages' => 1, 'offset' => 0]],
            'one row per page' => [1, 1, 1, ['page' => 1, 'total_pages' => 1, 'offset' => 0]],
        ];
    }

    public function testNormalizePaginationThrowsForZeroPageSize(): void
    {
        $this->expectException(DivisionByZeroError::class);

        (new AttendanceService())->normalizePagination(1, 0, 0);
    }

    #[DataProvider('rfidFeedbackCases')]
    public function testRfidFeedback(array $query, array $expected): void
    {
        self::assertSame($expected, (new AttendanceService())->rfidFeedback($query));
    }

    public static function rfidFeedbackCases(): array
    {
        return [
            'entry success uses default values' => [
                ['ok' => '1', 'nombre' => 'Ada'],
                [
                    'success' => [
                        'tipo' => 'entrada',
                        'nombre' => 'Ada',
                        'estado' => 'Normal',
                        'retardo' => '0',
                        'hora' => '',
                    ],
                    'error' => null,
                ],
            ],
            'entry success keeps supplied values' => [
                [
                    'ok' => '1',
                    'nombre' => 'Ada',
                    'estado' => 'Retardo',
                    'retardo' => '12',
                    'hora' => '08:12:00',
                ],
                [
                    'success' => [
                        'tipo' => 'entrada',
                        'nombre' => 'Ada',
                        'estado' => 'Retardo',
                        'retardo' => '12',
                        'hora' => '08:12:00',
                    ],
                    'error' => null,
                ],
            ],
            'exit success uses default values' => [
                ['ok' => '2'],
                [
                    'success' => [
                        'tipo' => 'salida',
                        'nombre' => '',
                        'hora' => '',
                        'temprana' => '0',
                    ],
                    'error' => null,
                ],
            ],
            'exit success keeps supplied values' => [
                ['ok' => '2', 'nombre' => 'Ada', 'hora' => '16:00:00', 'temprana' => '1'],
                [
                    'success' => [
                        'tipo' => 'salida',
                        'nombre' => 'Ada',
                        'hora' => '16:00:00',
                        'temprana' => '1',
                    ],
                    'error' => null,
                ],
            ],
            'unknown feedback code yields no message' => [
                ['ok' => '3', 'error' => '3'],
                ['success' => null, 'error' => null],
            ],
            'empty query yields no message' => [
                [],
                ['success' => null, 'error' => null],
            ],
            'entry and error feedback can coexist' => [
                ['ok' => '1', 'nombre' => 'Ada', 'error' => '1'],
                [
                    'success' => [
                        'tipo' => 'entrada',
                        'nombre' => 'Ada',
                        'estado' => 'Normal',
                        'retardo' => '0',
                        'hora' => '',
                    ],
                    'error' => 'La tarjeta no esta registrada en el sistema',
                ],
            ],
        ];
    }

    #[DataProvider('rfidErrorCases')]
    public function testRfidFeedbackErrorMessages(string $code, string $name, string $expected): void
    {
        self::assertSame(
            ['success' => null, 'error' => $expected],
            (new AttendanceService())->rfidFeedback(['error' => $code, 'nombre' => $name])
        );
    }

    public static function rfidErrorCases(): array
    {
        return [
            'unknown card' => [
                '1',
                'Ada',
                'La tarjeta no esta registrada en el sistema',
            ],
            'inactive learner' => [
                '2',
                'Ada',
                'Ada no se encuentra activo',
            ],
            'already completed attendance' => [
                '4',
                'Ada',
                'Ada ya completo su entrada y salida hoy',
            ],
        ];
    }
}
