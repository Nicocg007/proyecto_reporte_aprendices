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
            'filter values retain surrounding spaces' => [
                ['buscar' => ' Ana ', 'ficha' => '12', 'estado' => 'Activo'],
                ['buscar' => ' Ana ', 'ficha' => '12', 'estado' => 'Activo'],
            ],
            'empty filter values remain empty' => [
                ['buscar' => '', 'ficha' => '', 'estado' => ''],
                ['buscar' => '', 'ficha' => '', 'estado' => ''],
            ],
            'lowercase status is rejected' => [
                ['buscar' => 'Ana', 'ficha' => '12', 'estado' => 'activo'],
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
