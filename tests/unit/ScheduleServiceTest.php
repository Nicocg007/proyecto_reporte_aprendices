<?php

use App\Service\ScheduleService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScheduleServiceTest extends TestCase
{
    #[DataProvider('gridCases')]
    public function testBuildGrid(
        array $rows,
        string $minimumTime,
        string $maximumTime,
        array $expectedHours,
        array $expectedMap,
        array $expectedHolidays,
        array $expectedInstructors
    ): void {
        $service = new ScheduleService();
        $grid = $service->buildGrid($rows, $minimumTime, $maximumTime);

        self::assertSame($expectedHours, $grid['hours']);
        self::assertSame($expectedMap, $grid['map']);
        self::assertSame($expectedHolidays, $grid['holidays']);
        self::assertSame($expectedInstructors, $grid['instructors']);
    }

    public function testBuildWeek(): void
    {
        $week = (new ScheduleService())->buildWeek('2026-11-13', '2026-11-01', '2026-11-30');
        self::assertSame('2026-11-09', $week['monday']->format('Y-m-d'));
        self::assertSame('2026-11-14', $week['saturday']->format('Y-m-d'));
        self::assertTrue($week['has_previous']);
        self::assertTrue($week['has_next']);
    }

    public function testColorForInstructor(): void
    {
        self::assertSame(
            'blue',
            (new ScheduleService())->colorForInstructor('Instructor A', ['red', 'green', 'blue'])
        );
    }

    public static function gridCases(): array
    {
        $weekday = [
            'fecha' => '2026-11-09',
            'hora_inicio' => '08:00:00',
            'instructor' => 'Instructor A',
            'competencia' => 'Competencia',
            'es_festivo' => 0,
        ];
        $holiday = [
            'fecha' => '2026-11-10',
            'hora_inicio' => '08:00:00',
            'instructor' => '',
            'competencia' => '',
            'es_festivo' => 1,
        ];

        return [
            'weekday schedule and holiday are separated' => [
                [$weekday, $holiday],
                '08:00:00',
                '10:00:00',
                ['08:00', '09:00'],
                [0 => ['08:00' => $weekday]],
                [1 => true],
                ['Instructor A' => true],
            ],
            'weekend rows and zero-length time range are omitted' => [
                [[
                    'fecha' => '2026-11-15',
                    'hora_inicio' => '08:00:00',
                    'instructor' => 'Weekend Instructor',
                    'competencia' => 'Weekend Competency',
                    'es_festivo' => 0,
                ]],
                '08:00:00',
                '08:00:00',
                [],
                [],
                [],
                [],
            ],
        ];
    }
}
