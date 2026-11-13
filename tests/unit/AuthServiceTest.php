<?php

use App\Service\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testSessionDataIsPassedInAndRoleDestinationIsMapped(): void
    {
        $service = new AuthService();

        self::assertSame('instructor_dashboard.php', $service->dashboardForRole('Instructor'));
        self::assertSame('login.php', $service->dashboardForRole('unknown'));
        self::assertSame([
            'id' => 12,
            'documento' => 'DOC-12',
            'nombre' => 'Ada',
            'apellido' => 'Lovelace',
            'correo' => 'ada@example.test',
            'rol' => 'Aprendiz',
            'id_rol' => 3,
        ], $service->userSummary([
            'id_usuario' => 12,
            'numero_documento' => 'DOC-12',
            'nombre' => 'Ada',
            'apellido' => 'Lovelace',
            'correo' => 'ada@example.test',
            'rol' => 'Aprendiz',
            'id_rol' => 3,
        ]));
    }
}
