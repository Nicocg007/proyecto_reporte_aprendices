<?php

use App\Service\ReportService;
use PHPUnit\Framework\TestCase;

final class ReportServiceTest extends TestCase
{
    public function testCsvHeaders(): void
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
    }

    public function testCsvRow(): void
    {
        $service = new ReportService();

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
    }

    public function testCsvRowPreservesEmptyValues(): void
    {
        self::assertSame(
            ['', '', '', '', '', '', '', ''],
            (new ReportService())->csvRow([
                'nombre' => '',
                'documento' => '',
                'ficha' => '',
                'fecha' => '',
                'entrada' => '',
                'salida' => '',
                'retardo' => '',
                'estado' => '',
            ])
        );
    }

    public function testCsvRowWithMissingFields(): void
    {
        self::assertSame([
            'Ana Perez',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ], (new ReportService())->csvRow(['nombre' => 'Ana Perez']));
    }

    public function testCsvFilename(): void
    {
        self::assertSame(
            'reporte_asistencias_2026-11-13.csv',
            (new ReportService())->csvFilename('2026-11-13')
        );
    }
}
