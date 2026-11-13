<?php

declare(strict_types=1);

namespace App\Service;

final class FichaService
{
    public function validateCreation(array $data): bool
    {
        foreach (['codigo_ficha', 'nombre_programa', 'hora_entrada', 'hora_salida', 'id_instructor'] as $field) {
            if (($data[$field] ?? '') === '') {
                return false;
            }
        }

        return true;
    }
}
