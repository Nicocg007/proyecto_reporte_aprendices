<?php

declare(strict_types=1);

namespace App\Service;

final class ExcuseService
{
    private const ALLOWED_MIME_TYPES = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    public function validateSubmission(string $date, string $reason): ?string
    {
        return trim($date) === '' || trim($reason) === '' ? 'campos' : null;
    }

    public function validateAttachment(string $extension, int $size, string $mime): ?string
    {
        $extension = strtolower($extension);
        if (!isset(self::ALLOWED_MIME_TYPES[$extension])) {
            return 'archivo';
        }

        if ($size > 5 * 1024 * 1024) {
            return 'tamano';
        }

        if (!in_array($mime, self::ALLOWED_MIME_TYPES[$extension], true)) {
            return 'archivo';
        }

        return null;
    }

    public function notifications(array $counts, string $role): array
    {
        $notifications = [];
        if ($role === 'Administrador' || $role === 'Instructor') {
            $total = (int) ($counts['Pendiente'] ?? 0);
            if ($total > 0) {
                $notifications[] = [
                    'texto' => "Tienes $total excusa(s) pendiente(s) por revisar",
                    'url' => $role === 'Administrador' ? 'admin_excusas.php' : 'instructor_excusas.php',
                ];
            }
            return $notifications;
        }

        foreach ([
            'Pendiente' => 'Tienes %d excusa(s) en espera de revision',
            'Aprobada' => '%d excusa(s) aprobada(s)',
            'Rechazada' => '%d excusa(s) rechazada(s)',
        ] as $status => $message) {
            $total = (int) ($counts[$status] ?? 0);
            if ($total > 0) {
                $notifications[] = [
                    'texto' => sprintf($message, $total),
                    'url' => 'aprendiz_excusas.php',
                ];
            }
        }

        return $notifications;
    }
}
