<?php

declare(strict_types=1);

require_once __DIR__ . '/SqliteIntegrationTestCase.php';

use App\Repository\ExcuseRepository;

/**
 * Integracion: ExcuseRepository + PDO + SQL real (con 3 tablas en juego:
 * excusa, usuario_has_ficha y ficha; y al aprobar, tambien ingreso).
 *
 * Reglas de negocio cubiertas:
 *   - Toda excusa nueva nace en estado Pendiente
 *   - Cada rol ve solo lo que le corresponde (aprendiz lo suyo, instructor su ficha,
 *     administrador todo el sistema)
 *   - Solo el instructor encargado de la ficha puede revisar la excusa
 *   - Solo se revisa una vez (solo las Pendientes cambian de estado)
 *   - Aprobar una excusa restaura la asistencia del dia (estado Normal)
 *   - Rechazar no toca la asistencia
 */
final class ExcuseRepositoryIntegrationTest extends SqliteIntegrationTestCase
{
    private ExcuseRepository $repo;

    // ids de referencia compartidos por las pruebas
    private int $idAdmin;
    private int $idInstructor1;
    private int $idInstructor2;
    private int $idFicha1;
    private int $idFicha2;
    private int $idAprendiz1;
    private int $idAprendiz2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ExcuseRepository($this->pdo);

        // escenario base: roles canonico + 2 instructores, 2 fichas, 2 aprendices
        $this->crearRol('Administrador');
        $idRolInstructor = $this->crearRol('Instructor');
        $this->crearRol('Aprendiz');

        $this->idAdmin = $this->crearUsuario('ADM-1', 'Jefe', 'Sistema', 1);
        $this->idInstructor1 = $this->crearUsuario('INS-1', 'Guia', 'Uno', 2);
        $this->idInstructor2 = $this->crearUsuario('INS-2', 'Guia', 'Dos', 2);
        $this->idFicha1 = $this->crearFicha('3064749', 'ADSO', '07:00:00', '13:00:00', $this->idInstructor1);
        $this->idFicha2 = $this->crearFicha('3216504', 'ADSO Noche', '18:00:00', '22:00:00', $this->idInstructor2);
        $this->idAprendiz1 = $this->crearUsuario('APR-1', 'Ana', 'Uno', 3);
        $this->idAprendiz2 = $this->crearUsuario('APR-2', 'Ben', 'Dos', 3);
        $this->asignarAprendizAFicha($this->idAprendiz1, $this->idFicha1);
        $this->asignarAprendizAFicha($this->idAprendiz2, $this->idFicha2);
    }

    public function testCreatedExcuseStartsAsPendingAndLearnerSeesIt(): void
    {
        // preparar + actuar: el aprendiz 1 envia una excusa
        $this->repo->create($this->idAprendiz1, '2026-11-10', 'excusa.pdf', 'Cita medica');

        // afirmar: aparece en su lista como Pendiente
        $excusas = $this->repo->findLearnerExcuses($this->idAprendiz1);
        $this->assertCount(1, $excusas);
        $this->assertSame('Pendiente', $excusas[0]['estado']);
        $this->assertSame('Cita medica', $excusas[0]['motivo']);
    }

    public function testLearnerOnlySeesHisOwnExcuses(): void
    {
        // preparar: cada aprendiz envia su propia excusa
        $this->repo->create($this->idAprendiz1, '2026-11-10', 'a.pdf', 'De Ana');
        $this->repo->create($this->idAprendiz2, '2026-11-11', 'b.pdf', 'De Ben');

        // actuar + afirmar: Ana solo ve la suya, Ben solo la suya
        $this->assertCount(1, $this->repo->findLearnerExcuses($this->idAprendiz1));
        $this->assertSame('De Ana', $this->repo->findLearnerExcuses($this->idAprendiz1)[0]['motivo']);
        $this->assertCount(1, $this->repo->findLearnerExcuses($this->idAprendiz2));
        $this->assertSame('De Ben', $this->repo->findLearnerExcuses($this->idAprendiz2)[0]['motivo']);
    }

    public function testAdminNotificationCountsAllPendingInSystem(): void
    {
        // preparar: 2 pendientes (de fichas distintas) y 1 ya aprobada
        $this->repo->create($this->idAprendiz1, '2026-11-10', 'a.pdf', 'De Ana');
        $this->repo->create($this->idAprendiz2, '2026-11-11', 'b.pdf', 'De Ben');
        $this->repo->review(1, $this->idAdmin, 'Aprobada');

        // actuar + afirmar: al administrador le quedan 1 pendiente (la de Ben)
        $conteo = $this->repo->findNotificationCounts($this->idAdmin, 'Administrador');
        $this->assertSame(['Pendiente' => 1], $conteo);
    }

    public function testInstructorNotificationCountsOnlyHisFicha(): void
    {
        // preparar: 1 pendiente en ficha 1 y 2 pendientes en ficha 2
        $this->repo->create($this->idAprendiz1, '2026-11-10', 'a.pdf', 'De Ana');
        $this->repo->create($this->idAprendiz2, '2026-11-11', 'b1.pdf', 'De Ben 1');
        $this->repo->create($this->idAprendiz2, '2026-11-12', 'b2.pdf', 'De Ben 2');

        // actuar + afirmar: cada instructor solo cuenta las de SU ficha
        $this->assertSame(['Pendiente' => 1], $this->repo->findNotificationCounts($this->idInstructor1, 'Instructor'));
        $this->assertSame(['Pendiente' => 2], $this->repo->findNotificationCounts($this->idInstructor2, 'Instructor'));
    }

    public function testOnlyTheResponsibleInstructorCanReview(): void
    {
        // preparar: excusa del aprendiz de la ficha 1
        $this->repo->create($this->idAprendiz1, '2026-11-10', 'a.pdf', 'De Ana');

        // afirmar: su instructor SI puede revisarla; el otro instructor NO
        $this->assertTrue($this->repo->canInstructorReview(1, $this->idInstructor1));
        $this->assertFalse($this->repo->canInstructorReview(1, $this->idInstructor2));
    }

    public function testApprovingExcuseRestoresTheAttendanceRecord(): void
    {
        // preparar: el aprendiz 1 quedo con Retardo ese dia y envia excusa por esa fecha
        $stmt = $this->pdo->prepare(
            "INSERT INTO ingreso (id_aprendiz, fecha, minutos_retardo, estado_asistencia)
             VALUES (?, '2026-11-10', 25, 'Retardo')"
        );
        $stmt->execute([$this->idAprendiz1]);
        $idIngreso = (int) $this->pdo->lastInsertId();

        $this->repo->create($this->idAprendiz1, '2026-11-10', 'a.pdf', 'Cita medica');

        // actuar: el instructor de su ficha la aprueba
        $resultado = $this->repo->review(1, $this->idInstructor1, 'Aprobada');

        // afirmar: la excusa queda Aprobada con revisor, y la asistencia
        // del dia vuelve a Normal (la regla de negocio de la aprobacion)
        $this->assertTrue($resultado);
        $excusa = $this->pdo->query('SELECT estado, id_instructor_revisor, id_ingreso FROM excusa WHERE id_excusa = 1')->fetch();
        $this->assertSame('Aprobada', $excusa['estado']);
        $this->assertSame($this->idInstructor1, (int) $excusa['id_instructor_revisor']);
        $this->assertSame($idIngreso, (int) $excusa['id_ingreso']);

        $asistencia = $this->pdo->query("SELECT estado_asistencia, minutos_retardo FROM ingreso WHERE id_ingreso = $idIngreso")->fetch();
        $this->assertSame('Normal', $asistencia['estado_asistencia']);
        $this->assertSame(0, (int) $asistencia['minutos_retardo']);
    }

    public function testExcuseCanBeReviewedOnlyOnce(): void
    {
        // preparar: una excusa pendiente
        $this->repo->create($this->idAprendiz1, '2026-11-10', 'a.pdf', 'De Ana');

        // actuar: primera revision (aprueba), segundo intento
        $primera = $this->repo->review(1, $this->idInstructor1, 'Aprobada');
        $segunda = $this->repo->review(1, $this->idInstructor2, 'Rechazada');

        // afirmar: el segundo intento es rechazado y el estado queda intacto
        $this->assertTrue($primera);
        $this->assertFalse($segunda);
        $estado = $this->pdo->query('SELECT estado FROM excusa WHERE id_excusa = 1')->fetchColumn();
        $this->assertSame('Aprobada', $estado);
    }
}
