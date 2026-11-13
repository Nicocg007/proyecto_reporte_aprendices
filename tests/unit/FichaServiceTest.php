<?php

use App\Service\FichaService;
use PHPUnit\Framework\TestCase;

final class FichaServiceTest extends TestCase
{
    public function testCreationRequiresEveryField(): void
    {
        $service = new FichaService();
        $data = [
            'codigo_ficha' => '12345',
            'nombre_programa' => 'Desarrollo de software',
            'hora_entrada' => '08:00',
            'hora_salida' => '17:00',
            'id_instructor' => '7',
        ];

        self::assertTrue($service->validateCreation($data));

        unset($data['id_instructor']);
        self::assertFalse($service->validateCreation($data));
        self::assertFalse($service->validateCreation([]));
    }
}
