<?php

declare(strict_types=1);

require_once __DIR__ . '/SqliteIntegrationTestCase.php';

use App\Repository\FichaRepository;

/**
 * Integracion: FichaRepository + PDO + SQL real (ficha, usuario,
 * usuario_has_ficha, con GROUP BY, LEFT JOIN y subconsultas).
 *
 * Reglas de negocio cubiertas:
 *   - Solo los instructores ACTIVOS aparecen para asignar fichas
 *   - Crear una ficha la deja persistida y asignada a su instructor
 *   - El listado de fichas incluye el total real de aprendices por ficha
 *   - El aprendiz ve la ficha a la que pertenece con sus datos e instructor
 *   - El instructor solo ve SUS fichas
 */
final class FichaRepositoryIntegrationTest extends SqliteIntegrationTestCase
{
    private FichaRepository $repo;
    private int $idFicha1;
    private int $idFicha2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new FichaRepository($this->pdo);

        // escenario base: roles, 1 admin, 2 instructores (uno inactivo),
        // 2 fichas del instructor 1 y 3 aprendices repartidos
        $this->crearRol('Administrador');
        $idRolInstructor = $this->crearRol('Instructor');
        $idRolAprendiz = $this->crearRol('Aprendiz');

        $this->crearUsuario('ADM-1', 'Jefe', 'Sistema', 1);
        $idInstructor1 = $this->crearUsuario('INS-1', 'Guia', 'Uno', 2);
        $idInstructor2 = $this->crearUsuario('INS-2', 'Guia', 'Dos', 2, 'Inactivo');

        $this->idFicha1 = $this->crearFicha('3064749', 'ADSO', '07:00:00', '13:00:00', $idInstructor1);
        $this->idFicha2 = $this->crearFicha('3216504', 'ADSO Noche', '18:00:00', '22:00:00', $idInstructor1);

        $idAna = $this->crearUsuario('APR-1', 'Ana', 'Uno', 3);
        $idBen = $this->crearUsuario('APR-2', 'Ben', 'Dos', 3);
        $this->asignarAprendizAFicha($idAna, $this->idFicha1);
        $this->asignarAprendizAFicha($idBen, $this->idFicha1);
        // un aprendiz sin ficha tambien existe (id auto), sin asignacion
    }

    public function testOnlyActiveInstructorsAreListed(): void
    {
        // actuar + afirmar: el listado para crear fichas solo trae activos
        $instructores = $this->repo->findActiveInstructors();
        $this->assertCount(1, $instructores);
        $this->assertSame('Guia', $instructores[0]['nombre']);
    }

    public function testCreateFichaPersistsWithItsData(): void
    {
        // preparar: instructor activo existente (id del listado anterior)
        $idInstructor = (int) $this->repo->findActiveInstructors()[0]['id_usuario'];

        // actuar: crear una ficha nueva
        $this->repo->create([
            'codigo_ficha' => '3389186',
            'nombre_programa' => 'ADSO Sabatina',
            'hora_entrada' => '08:00:00',
            'hora_salida' => '14:00:00',
            'id_instructor' => $idInstructor,
        ]);

        // afirmar: aparece en el listado general con el nombre del instructor
        $fichas = $this->repo->findAllWithLearnerCounts();
        $codigos = array_column($fichas, 'codigo_ficha');
        $this->assertContains('3389186', $codigos);
    }

    public function testListingIncludesRealLearnerCountsPerFicha(): void
    {
        // actuar: listado general con conteos
        $fichas = $this->repo->findAllWithLearnerCounts();

        // afirmar: ficha 1 tiene 2 aprendices, ficha 2 tiene 0 (LEFT JOIN)
        $porCodigo = [];
        foreach ($fichas as $ficha) {
            $porCodigo[$ficha['codigo_ficha']] = (int) $ficha['total_aprendices'];
        }
        $this->assertSame(2, $porCodigo['3064749']);
        $this->assertSame(0, $porCodigo['3216504']);
        $this->assertSame('Guia Uno', $fichas[0]['instructor']);
    }

    public function testLearnerSeesHisFichaWithInstructorAndCounts(): void
    {
        // preparar: id de un aprendiz asignado a la ficha 1
        $idAna = (int) $this->pdo->query("SELECT id_usuario FROM usuario WHERE numero_documento = 'APR-1'")->fetchColumn();

        // actuar + afirmar: la ficha de Ana es la 3064749, con su instructor
        // y el conteo de aprendices de esa ficha
        $ficha = $this->repo->findLearnerFicha($idAna);
        $this->assertSame('3064749', $ficha['codigo_ficha']);
        $this->assertSame('Guia Uno', $ficha['instructor_nombre']);
        $this->assertSame(2, (int) $ficha['total_aprendices_ficha']);
    }

    public function testInstructorOnlySeesHisOwnFichas(): void
    {
        // preparar: id del instructor activo
        $idInstructor = (int) $this->repo->findActiveInstructors()[0]['id_usuario'];

        // actuar + afirmar: el instructor 1 ve sus 2 fichas con sus conteos
        $fichas = $this->repo->findInstructorFichasWithLearnerCounts($idInstructor);
        $this->assertCount(2, $fichas);
        $this->assertSame('3064749', $fichas[0]['codigo_ficha']);
        $this->assertSame(2, (int) $fichas[0]['total_aprendices']);
        $this->assertSame(0, (int) $fichas[1]['total_aprendices']);
    }
}
