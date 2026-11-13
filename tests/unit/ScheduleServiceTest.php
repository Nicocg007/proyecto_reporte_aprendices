<?php

use App\Service\ScheduleService;
use PHPUnit\Framework\TestCase;

final class ScheduleServiceTest extends TestCase
{
    public function testBuildGridKeepsWeekdayEntriesAndSeparatesHolidays(): void
    {
        $grid = (new ScheduleService())->buildGrid([
            [
                'fecha' => '2026-11-09',
                'hora_inicio' => '08:00:00',
                'instructor' => 'Instructor A',
                'competencia' => 'Competencia',
                'es_festivo' => 0,
            ],
            [
                'fecha' => '2026-11-10',
                'hora_inicio' => '08:00:00',
                'instructor' => '',
                'competencia' => '',
                'es_festivo' => 1,
            ],
        ], '08:00:00', '10:00:00');

        self::assertSame(['08:00', '09:00'], $grid['hours']);
        self::assertSame('Competencia', $grid['map'][0]['08:00']['competencia']);
        self::assertSame([1 => true], $grid['holidays']);
        self::assertSame(['Instructor A' => true], $grid['instructors']);
    }
}
