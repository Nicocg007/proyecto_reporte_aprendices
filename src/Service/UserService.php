<?php

declare(strict_types=1);

namespace App\Service;

final class UserService
{
    public function normalizeLearnerFilters(array $filters): array
    {
        $status = trim((string) ($filters['estado'] ?? ''));
        if (strcasecmp($status, 'Activo') === 0) {
            $status = 'Activo';
        } elseif (strcasecmp($status, 'Inactivo') === 0) {
            $status = 'Inactivo';
        } else {
            $status = '';
        }

        return [
            'buscar' => trim((string) ($filters['buscar'] ?? '')),
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
