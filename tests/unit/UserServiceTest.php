<?php

use App\Service\UserService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    #[DataProvider('learnerFilterCases')]
    public function testLearnerFilters(array $filters, array $expected): void
    {
        self::assertSame($expected, (new UserService())->normalizeLearnerFilters($filters));
    }

    public static function learnerFilterCases(): array
    {
        return [
            'leading spaces in search are trimmed' => [
                ['buscar' => ' Ana', 'ficha' => '12', 'estado' => 'Activo'],
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'Activo'],
            ],
            'trailing spaces in search are trimmed' => [
                ['buscar' => 'Ana ', 'ficha' => '12', 'estado' => 'Activo'],
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'Activo'],
            ],
            'whitespace-only search becomes empty' => [
                ['buscar' => '   ', 'ficha' => '12', 'estado' => 'Activo'],
                ['buscar' => '', 'ficha' => '12', 'estado' => 'Activo'],
            ],
            'empty filter values remain empty' => [
                ['buscar' => '', 'ficha' => '', 'estado' => ''],
                ['buscar' => '', 'ficha' => '', 'estado' => ''],
            ],
            'lowercase active status is normalized' => [
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'activo'],
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'Activo'],
            ],
            'uppercase active status is normalized' => [
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'ACTIVO'],
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'Activo'],
            ],
            'status with surrounding spaces is normalized' => [
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => ' Activo '],
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'Activo'],
            ],
            'lowercase inactive status is normalized' => [
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'inactivo'],
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'Inactivo'],
            ],
            'invalid status is discarded' => [
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'Desconocido'],
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => ''],
            ],
            'missing filter keys default to empty values' => [
                [],
                ['buscar' => '', 'ficha' => '', 'estado' => ''],
            ],
        ];
    }

    #[DataProvider('learnerNameCases')]
    public function testLearnerNameValidation(string $name, ?string $expected): void
    {
        self::assertSame($expected, (new UserService())->validateLearnerName($name));
    }

    public static function learnerNameCases(): array
    {
        return [
            'four characters is too short' => [
                'Luis',
                'el nombre debe de contener al menos 5 caracteres',
            ],
            'five characters is accepted' => ['Maria', null],
            'surrounding spaces do not affect length' => ['  Maria  ', null],
            'empty name is too short' => [
                '',
                'el nombre debe de contener al menos 5 caracteres',
            ],
        ];
    }
}
