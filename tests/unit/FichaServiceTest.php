<?php

use App\Service\FichaService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FichaServiceTest extends TestCase
{
    private const VALID_DATA = [
        'codigo_ficha' => '12345',
        'nombre_programa' => 'Desarrollo de software',
        'hora_entrada' => '08:00',
        'hora_salida' => '17:00',
        'id_instructor' => '7',
    ];

    public function testCreationAcceptsAllRequiredFields(): void
    {
        self::assertTrue((new FichaService())->validateCreation(self::VALID_DATA));
    }

    #[DataProvider('missingFieldCases')]
    public function testCreationRejectsEachMissingField(string $field): void
    {
        $data = self::VALID_DATA;
        unset($data[$field]);

        self::assertFalse((new FichaService())->validateCreation($data));
    }

    public static function missingFieldCases(): array
    {
        return [
            'missing ficha code' => ['codigo_ficha'],
            'missing program name' => ['nombre_programa'],
            'missing entry time' => ['hora_entrada'],
            'missing exit time' => ['hora_salida'],
            'missing instructor' => ['id_instructor'],
        ];
    }

    #[DataProvider('emptyFieldCases')]
    public function testCreationRejectsEachEmptyField(string $field): void
    {
        $data = self::VALID_DATA;
        $data[$field] = '';

        self::assertFalse((new FichaService())->validateCreation($data));
    }

    public static function emptyFieldCases(): array
    {
        return [
            'empty ficha code' => ['codigo_ficha'],
            'empty program name' => ['nombre_programa'],
            'empty entry time' => ['hora_entrada'],
            'empty exit time' => ['hora_salida'],
            'empty instructor' => ['id_instructor'],
        ];
    }

    public function testCreationRejectsFieldsContainingOnlySpaces(): void
    {
        $data = array_fill_keys(array_keys(self::VALID_DATA), '   ');

        self::assertFalse((new FichaService())->validateCreation($data));
    }
}
