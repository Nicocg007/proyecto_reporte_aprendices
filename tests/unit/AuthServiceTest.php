<?php

use App\Service\AuthService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    #[DataProvider('dashboardRoleCases')]
    public function testDashboardForRole(string $role, string $expected): void
    {
        self::assertSame($expected, (new AuthService())->dashboardForRole($role));
    }

    public static function dashboardRoleCases(): array
    {
        return [
            'administrator dashboard' => ['Administrador', 'admin_dashboard.php'],
            'instructor dashboard' => ['Instructor', 'instructor_dashboard.php'],
            'learner dashboard' => ['Aprendiz', 'aprendiz_dashboard.php'],
            'empty role redirects to login' => ['', 'login.php'],
            'unknown role redirects to login' => ['unknown', 'login.php'],
        ];
    }

    public function testUserSummaryContainsCompleteSessionData(): void
    {
        self::assertSame([
            'id' => 12,
            'documento' => 'DOC-12',
            'nombre' => 'Ada',
            'apellido' => 'Lovelace',
            'correo' => 'ada@example.test',
            'rol' => 'Aprendiz',
            'id_rol' => 3,
        ], (new AuthService())->userSummary([
            'id_usuario' => 12,
            'numero_documento' => 'DOC-12',
            'nombre' => 'Ada',
            'apellido' => 'Lovelace',
            'correo' => 'ada@example.test',
            'rol' => 'Aprendiz',
            'id_rol' => 3,
        ]));
    }

    public function testUserSummaryDefaultsMissingSessionFields(): void
    {
        self::assertSame([
            'id' => 12,
            'documento' => '',
            'nombre' => '',
            'apellido' => '',
            'correo' => '',
            'rol' => '',
            'id_rol' => 0,
        ], (new AuthService())->userSummary([
            'id_usuario' => 12,
        ]));
    }
}
