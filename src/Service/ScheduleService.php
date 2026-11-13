<?php

declare(strict_types=1);

namespace App\Service;

use DateTime;

final class ScheduleService
{
    public function buildWeek(string $initialDate, ?string $firstDate, ?string $lastDate): array
    {
        if ($initialDate !== '') {
            $monday = new DateTime($initialDate);
        } elseif ($firstDate) {
            $monday = new DateTime($firstDate);
        } else {
            $monday = new DateTime('monday this week');
        }
        $monday->modify('monday this week');
        $saturday = (clone $monday)->modify('+5 days');

        return [
            'monday' => $monday,
            'saturday' => $saturday,
            'has_previous' => $firstDate && $monday->format('Y-m-d') > $firstDate,
            'has_next' => $lastDate && $saturday->format('Y-m-d') < $lastDate,
        ];
    }

    public function buildGrid(array $rows, string $minimumTime, string $maximumTime): array
    {
        $map = [];
        $holidays = [];
        foreach ($rows as $row) {
            $day = (int) date('N', strtotime($row['fecha'])) - 1;
            if ($day < 0 || $day > 5) {
                continue;
            }
            if ((int) $row['es_festivo'] === 1) {
                $holidays[$day] = true;
                continue;
            }
            $map[$day][substr($row['hora_inicio'], 0, 5)] = $row;
        }

        $hours = [];
        $time = strtotime($minimumTime);
        $end = strtotime($maximumTime);
        while ($time < $end) {
            $hours[] = date('H:i', $time);
            $time = strtotime('+1 hour', $time);
        }

        $instructors = [];
        foreach ($map as $byHour) {
            foreach ($byHour as $row) {
                $instructors[$row['instructor']] = true;
            }
        }
        ksort($instructors);

        return [
            'map' => $map,
            'holidays' => $holidays,
            'hours' => $hours,
            'instructors' => $instructors,
        ];
    }

    public function colorForInstructor(string $name, array $palette): string
    {
        return $palette[crc32($name) % count($palette)];
    }
}
