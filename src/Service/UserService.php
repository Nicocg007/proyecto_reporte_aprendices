<?php

declare(strict_types=1);

namespace App\Service;

final class UserService
{
    public function normalizeLearnerFilters(array $filters): array
    {
        $status = (string) ($filters['estado'] ?? '');
        if ($status !== '' && !in_array($status, ['Activo', 'Inactivo'], true)) {
            $status = '';
        }

        return [
            'buscar' => (string) ($filters['buscar'] ?? ''),
            'ficha' => $filters['ficha'] ?? '',
            'estado' => $status,
        ];
    }

    public function validateLearnerName(string $name): ?string
    {
        return mb_strlen(trim($name), 'UTF-8') < 5
            ? 'el nombre debe de contener al menos 5 caracteres'
            : null;
    }
}
