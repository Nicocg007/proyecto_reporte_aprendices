<?php

declare(strict_types=1);

namespace App\Service;

final class AuthService
{
    public function isAuthenticated(array $session): bool
    {
        return isset($session['id_usuario']);
    }

    public function userSummary(array $session): ?array
    {
        if (!$this->isAuthenticated($session)) {
            return null;
        }

        return [
            'id' => $session['id_usuario'],
            'documento' => $session['numero_documento'] ?? '',
            'nombre' => $session['nombre'] ?? '',
            'apellido' => $session['apellido'] ?? '',
            'correo' => $session['correo'] ?? '',
            'rol' => $session['rol'] ?? '',
            'id_rol' => $session['id_rol'] ?? 0,
        ];
    }

    public function dashboardForRole(string $role): string
    {
        $destinations = [
            'Administrador' => 'admin_dashboard.php',
            'Instructor' => 'instructor_dashboard.php',
            'Aprendiz' => 'aprendiz_dashboard.php',
        ];

        return $destinations[$role] ?? 'login.php';
    }
}
