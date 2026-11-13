<?php

declare(strict_types=1);

namespace App\Service;

final class AttendanceService
{
    private const STATES = ['Normal', 'Retardo', 'Inasistencia', 'Salida Temprana'];

    public function allowedStatuses(): array
    {
        return self::STATES;
    }

    public function calculateCheckIn(string $currentTime, ?string $scheduledTime): array
    {
        $status = 'Normal';
        $lateMinutes = 0;

        if ($scheduledTime !== null && $currentTime > $scheduledTime) {
            $status = 'Retardo';
            $lateMinutes = max(
                1,
                (int) round(
                    (strtotime($currentTime) - strtotime($scheduledTime)) / 60
                )
            );
        }

        return ['estado' => $status, 'minutos_retardo' => $lateMinutes];
    }

    public function calculateManualAttendance(
        ?string $checkIn,
        string $status,
        ?string $scheduledTime
    ): array {
        if (!in_array($status, self::STATES, true)) {
            $status = 'Inasistencia';
        }

        $lateMinutes = 0;
        if ($checkIn !== null && $status !== 'Inasistencia' && $scheduledTime !== null) {
            $scheduledMinutes = $this->minutesSinceMidnight($scheduledTime);
            $checkInMinutes = $this->minutesSinceMidnight($checkIn);
            $lateMinutes = max(0, $checkInMinutes - $scheduledMinutes);
        }

        return [
            'estado' => $status,
            'minutos_retardo' => $lateMinutes,
            'salida_temprana' => $status === 'Salida Temprana' ? 1 : 0,
        ];
    }

    public function normalizeReportFilters(array $filters): array
    {
        $normalized = [
            'fecha_inicio' => trim((string) ($filters['fecha_inicio'] ?? '')),
            'fecha_fin' => trim((string) ($filters['fecha_fin'] ?? '')),
            'ficha' => $filters['ficha'] ?? '',
            'estado' => trim((string) ($filters['estado'] ?? '')),
        ];

        if (
            $normalized['estado'] !== ''
            && !in_array($normalized['estado'], self::STATES, true)
        ) {
            $normalized['estado'] = '';
        }

        foreach (['fecha_inicio', 'fecha_fin'] as $dateField) {
            if (
                $normalized[$dateField] !== ''
                && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized[$dateField])
            ) {
                $normalized[$dateField] = '';
            }
        }

        return $normalized;
    }

    public function countByStatus(array $rows): array
    {
        $totals = array_fill_keys(self::STATES, 0);

        foreach ($rows as $row) {
            if (isset($totals[$row['estado']])) {
                $totals[$row['estado']]++;
            }
        }

        return $totals;
    }

    public function normalizePagination(int $page, int $perPage, int $totalRows): array
    {
        if ($perPage <= 0) {
            throw new \InvalidArgumentException('perPage debe ser mayor o igual a 1.');
        }

        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = min(max(1, $page), $totalPages);

        return [
            'page' => $page,
            'total_pages' => $totalPages,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    public function rfidFeedback(array $query): array
    {
        $success = null;
        $errorMessage = null;
        $ok = $query['ok'] ?? '';
        $error = $query['error'] ?? '';
        $name = $query['nombre'] ?? '';

        if ($ok === '1') {
            $success = [
                'tipo' => 'entrada',
                'nombre' => $name,
                'estado' => $query['estado'] ?? 'Normal',
                'retardo' => $query['retardo'] ?? '0',
                'hora' => $query['hora'] ?? '',
            ];
        } elseif ($ok === '2') {
            $success = [
                'tipo' => 'salida',
                'nombre' => $name,
                'hora' => $query['hora'] ?? '',
                'temprana' => $query['temprana'] ?? '0',
            ];
        }

        if ($error === '1') {
            $errorMessage = 'La tarjeta no esta registrada en el sistema';
        } elseif ($error === '2') {
            $errorMessage = $name . ' no se encuentra activo';
        } elseif ($error === '4') {
            $errorMessage = $name . ' ya completo su entrada y salida hoy';
        }

        return ['success' => $success, 'error' => $errorMessage];
    }

    private function minutesSinceMidnight(string $time): int
    {
        return ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
    }
}
