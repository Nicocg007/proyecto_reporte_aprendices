<?php

use App\Service\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testLearnerFiltersAndNameValidation(): void
    {
        $service = new UserService();

        self::assertSame([
            'buscar' => ' Ana ',
            'ficha' => '12',
            'estado' => 'Activo',
        ], $service->normalizeLearnerFilters([
            'buscar' => ' Ana ',
            'ficha' => '12',
            'estado' => 'Activo',
        ]));
        self::assertSame([
            'buscar' => '',
            'ficha' => '',
            'estado' => '',
        ], $service->normalizeLearnerFilters(['estado' => 'activo']));

        self::assertNull($service->validateLearnerName('  Maria  '));
        self::assertSame(
            'el nombre debe de contener al menos 5 caracteres',
            $service->validateLearnerName(' Ana ')
        );
    }
}
