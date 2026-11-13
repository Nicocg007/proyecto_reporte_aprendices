<?php

declare(strict_types=1);

namespace App\Service;

final class ReportService
{
    public function csvHeaders(): array
    {
        return ['Aprendiz', 'Documento', 'Ficha', 'Fecha', 'Entrada', 'Salida', 'Retardo (min)', 'Estado'];
    }

    public function csvRow(array $attendance): array
    {
        return [
            $attendance['nombre'],
            $attendance['documento'],
            $attendance['ficha'],
            $attendance['fecha'],
            $attendance['entrada'],
            $attendance['salida'],
            $attendance['retardo'],
            $attendance['estado'],
        ];
    }

    public function csvFilename(string $date): string
    {
        return 'reporte_asistencias_' . $date . '.csv';
    }
}
