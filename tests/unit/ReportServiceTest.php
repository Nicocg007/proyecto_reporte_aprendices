<?php

use App\Service\ReportService;
use PHPUnit\Framework\TestCase;

final class ReportServiceTest extends TestCase
{
    public function testCsvHeadersRowsAndFilename(): void
    {
        $service = new ReportService();

        self::assertSame([
            'Aprendiz',
            'Documento',
            'Ficha',
            'Fecha',
            'Entrada',
            'Salida',
            'Retardo (min)',
            'Estado',
        ], $service->csvHeaders());
        self::assertSame([
            'Ana Perez',
            '123456',
            '789',
            '2026-11-13',
            '08:05',
            '17:00',
            5,
            'Retardo',
        ], $service->csvRow([
            'nombre' => 'Ana Perez',
            'documento' => '123456',
            'ficha' => '789',
            'fecha' => '2026-11-13',
            'entrada' => '08:05',
            'salida' => '17:00',
            'retardo' => 5,
            'estado' => 'Retardo',
        ]));
        self::assertSame(
            'reporte_asistencias_2026-11-13.csv',
            $service->csvFilename('2026-11-13')
        );
    }
}
