<?php

declare(strict_types=1);

require_once __DIR__ . '/SqliteIntegrationTestCase.php';

use App\Repository\UserRepository;

/**
 * Integracion: UserRepository + PDO + SQL real contra el esquema del proyecto.
 *
 * Reglas de negocio cubiertas:
 *   - El login busca por numero de documento y devuelve el rol del usuario
 *   - Las consultas usan consultas preparadas (la inyeccion SQL no devuelve datos)
 *   - El CRUD de aprendices persiste datos y su asignacion a ficha
 *   - La actualizacion de correo y contrasena se refleja en la base
 */
final class UserRepositoryIntegrationTest extends SqliteIntegrationTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepository($this->pdo);
    }

    public function testFindForLoginReturnsUserWithItsRole(): void
    {
        // preparar: rol Administrador (id_rol 1) y un usuario de ese rol
        $idRolAdmin = $this->crearRol('Administrador');
        $this->crearUsuario('DOC-1', 'Nico', 'Gomez', $idRolAdmin);

        // actuar: buscar por documento como lo hace el login
        $usuario = $this->repo->findForLogin('DOC-1');

        // afirmar: viene el usuario con su rol unido por la FK
        $this->assertNotNull($usuario);
        $this->assertSame('Nico', $usuario['nombre']);
        $this->assertSame('Administrador', $usuario['rol']);
        $this->assertSame('Activo', $usuario['estado']);
    }

    public function testFindForLoginReturnsNullForUnknownDocument(): void
    {
        // preparar: base con un usuario, pero se busca uno que no existe
        $idRol = $this->crearRol('Aprendiz');
        $this->crearUsuario('DOC-1', 'Ada', 'Lovelace', $idRol);

        // actuar + afirmar: documento inexistente no devuelve nada
        $this->assertNull($this->repo->findForLogin('DOC-QUE-NO-EXISTE'));
    }

    public function testFindForLoginSurvivesSqlInjectionAttempt(): void
    {
        // preparar: hay usuarios en la base
        $idRol = $this->crearRol('Aprendiz');
        $this->crearUsuario('DOC-1', 'Ada', 'Lovelace', $idRol);

        // actuar + afirmar: el intento clasico de inyeccion no devuelve datos
        // porque el repositorio usa consultas preparadas
        $this->assertNull($this->repo->findForLogin("' OR 1=1 --"));
    }

    public function testCreateLearnerPersistsUserAndFichaAssignment(): void
    {
        // preparar: roles en el orden canonico del sistema (1=Admin, 2=Instructor, 3=Aprendiz)
        // porque createLearner() asigna id_rol = 3 fijo a los aprendices
        $this->crearRol('Administrador');
        $idRolInstructor = $this->crearRol('Instructor');
        $this->crearRol('Aprendiz');
        $idInstructor = $this->crearUsuario('INS-1', 'Guia', 'Sena', $idRolInstructor);
        $idFicha = $this->crearFicha('3064749', 'ADSO', '07:00:00', '13:00:00', $idInstructor);

        // actuar: crear el aprendiz ya asignado a la ficha (2 INSERT en una operacion)
        $this->repo->createLearner([
            'documento' => 'APR-1',
            'nombre' => 'Ana',
            'apellido' => 'Aprendiz',
            'correo' => 'ana@example.test',
            'rfid' => 'RFID-1',
            'ficha' => (string) $idFicha,
        ], 'hash-de-clave');

        // el id del usuario nuevo: buscarlo por su documento unico
        // (lastInsertId() aqui daria el id de la asignacion a ficha, no el del usuario)
        $idAprendiz = (int) $this->repo->findByDocument('APR-1')['id_usuario'];

        // afirmar: el aprendiz existe, esta en rol 3 y su ficha quedo asignada
        $aprendiz = $this->repo->findLearner($idAprendiz);
        $this->assertNotNull($aprendiz);
        $this->assertSame('Ana', $aprendiz['nombre']);
        $this->assertSame((string) $idFicha, (string) $aprendiz['id_ficha']);
        $this->assertSame('Activo', $aprendiz['estado']);
    }

    public function testUpdateEmailIsReflectedInProfile(): void
    {
        // preparar: usuario con correo inicial
        $idRol = $this->crearRol('Aprendiz');
        $idUsuario = $this->crearUsuario('DOC-1', 'Ada', 'Lovelace', $idRol);

        // actuar: cambiar el correo
        $this->repo->updateEmail($idUsuario, 'nuevo@example.test');

        // afirmar: el perfil leido de la BD muestra el correo nuevo
        $perfil = $this->repo->findProfile($idUsuario);
        $this->assertSame('nuevo@example.test', $perfil['correo']);
        $this->assertSame('Ada', $perfil['nombre']);
    }

    public function testUpdatePasswordChangesStoredHash(): void
    {
        // preparar: usuario con hash inicial 'hash-inicial'
        $idRol = $this->crearRol('Aprendiz');
        $idUsuario = $this->crearUsuario('DOC-1', 'Ada', 'Lovelace', $idRol);

        // actuar: actualizar la contrasena con un hash real de PHP
        $hashNuevo = password_hash('ClaveSegura2024', PASSWORD_DEFAULT);
        $this->repo->updatePassword($idUsuario, $hashNuevo);

        // afirmar: lo guardado en la BD es el hash nuevo (y sirve para verificar)
        $hashGuardado = $this->repo->findPasswordHash($idUsuario);
        $this->assertSame($hashNuevo, $hashGuardado);
        $this->assertTrue(password_verify('ClaveSegura2024', $hashGuardado));
    }
}
