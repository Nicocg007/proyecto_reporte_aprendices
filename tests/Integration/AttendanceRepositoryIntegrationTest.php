<?php

declare(strict_types=1);

require_once __DIR__ . '/SqliteIntegrationTestCase.php';

use App\Repository\AttendanceRepository;

/**
 * Integracion: AttendanceRepository + AttendanceService (en marcacion manual)
 * + PDO + SQL real contra ingreso, usuario, usuario_has_ficha y ficha.
 *
 * Reglas de negocio cubiertas:
 *   - El RFID identifica al aprendiz para el marcaje con la tarjeta
 *   - El horario del aprendiz viene de la ficha a la que esta asignado
 *   - La entrada se registra con fecha del dia o fecha indicada, y se puede
 *     actualizar si ya existia un registro
 *   - La salida registra la hora y el estado de salida temprana
 *   - markAbsences marca Inasistencia a los aprendices activos sin registro
 *     del dia, solo cuando ya paso la hora de salida de su ficha
 */
final class AttendanceRepositoryIntegrationTest extends SqliteIntegrationTestCase
{
    private AttendanceRepository $repo;
    private int $idAprendiz1;
    private int $idAprendiz2;
    private int $idAprendizSinFicha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AttendanceRepository($this->pdo);

        // escenario base: 2 aprendices en la misma ficha (sale 13:00) y 1 sin ficha
        $this->crearRol('Administrador');
        $idRolInstructor = $this->crearRol('Instructor');
        $this->crearRol('Aprendiz');

        $idInstructor = $this->crearUsuario('INS-1', 'Guia', 'Sena', 2);
        $this->idAprendiz1 = $this->crearUsuario('APR-1', 'Ana', 'Uno', 3, 'Activo', 'RFID-ANA');
        $this->idAprendiz2 = $this->crearUsuario('APR-2', 'Ben', 'Dos', 3, 'Activo', 'RFID-BEN');
        $this->idAprendizSinFicha = $this->crearUsuario('APR-3', 'Carl', 'Tres', 3);

        $idFicha = $this->crearFicha('3064749', 'ADSO', '07:00:00', '13:00:00', $idInstructor);
        $this->asignarAprendizAFicha($this->idAprendiz1, $idFicha);
        $this->asignarAprendizAFicha($this->idAprendiz2, $idFicha);
    }

    public function testRfidIdentifiesLearnerAndUnknownCardReturnsNothing(): void
    {
        // actuar + afirmar: la tarjeta de Ana la identifica; una tarjeta
        // desconocida devuelve null (regla del lector RFID)
        $aprendiz = $this->repo->findByRfid('RFID-ANA');
        $this->assertNotNull($aprendiz);
        $this->assertSame('Ana', $aprendiz['nombre']);
        $this->assertNull($this->repo->findByRfid('RFID-DESCONOCIDA'));
    }

    public function testLearnerScheduleComesFromHisFicha(): void
    {
        // actuar + afirmar: el horario de Ana es el de su ficha (07:00 - 13:00)
        $horario = $this->repo->findScheduleForLearner($this->idAprendiz1);
        $this->assertSame('07:00:00', $horario['hora_entrada']);
        $this->assertSame('13:00:00', $horario['hora_salida']);

        // y un aprendiz sin ficha no tiene horario
        $this->assertNull($this->repo->findScheduleForLearner($this->idAprendizSinFicha));
    }

    public function testSaveCheckInPersistsTheAttendanceForTheGivenDate(): void
    {
        // actuar: Ana marca entrada tarde el 2026-11-13
        $this->repo->saveCheckIn($this->idAprendiz1, '2026-11-13', '07:20:00', 20, 'Retardo', null);

        // afirmar: el registro existe con los datos correctos
        $asistencia = $this->repo->findForDate($this->idAprendiz1, '2026-11-13');
        $this->assertNotNull($asistencia);
        $this->assertSame('Retardo', $asistencia['estado_asistencia']);
        $this->assertSame('07:20:00', $asistencia['hora_entrada_registrada']);
    }

    public function testSaveCheckInWithoutDateUsesToday(): void
    {
        // actuar: marcaje sin fecha explicita (lo que hace el lector RFID)
        $this->repo->saveCheckIn($this->idAprendiz1, null, '07:00:00', 0, 'Normal', null);

        // afirmar: aparece como asistencia de HOY (CURDATE)
        $hoy = $this->repo->findForToday($this->idAprendiz1);
        $this->assertNotNull($hoy);
        $this->assertSame('Normal', $hoy['estado_asistencia']);
    }

    public function testSaveCheckInUpdatesAnExistingRecord(): void
    {
        // preparar: Ana ya tenia registro del dia
        $this->repo->saveCheckIn($this->idAprendiz1, '2026-11-13', '07:05:00', 5, 'Retardo', null);
        $idAsistencia = (int) $this->repo->findForDate($this->idAprendiz1, '2026-11-13')['id_ingreso'];

        // actuar: re-marcacion sobre el mismo registro (UPDATE, no INSERT)
        $this->repo->saveCheckIn($this->idAprendiz1, '2026-11-13', '06:55:00', 0, 'Normal', $idAsistencia);

        // afirmar: se actualizo en lugar de duplicarse
        $asistencia = $this->repo->findForDate($this->idAprendiz1, '2026-11-13');
        $this->assertSame('Normal', $asistencia['estado_asistencia']);
        $this->assertSame('06:55:00', $asistencia['hora_entrada_registrada']);
        $this->assertSame($idAsistencia, (int) $asistencia['id_ingreso']);
    }

    public function testSaveCheckOutRegistersTimeAndEarlyDeparture(): void
    {
        // preparar: entrada normal
        $this->repo->saveCheckIn($this->idAprendiz1, '2026-11-13', '07:00:00', 0, 'Normal', null);
        $idAsistencia = (int) $this->repo->findForDate($this->idAprendiz1, '2026-11-13')['id_ingreso'];

        // actuar: sale temprano a las 10:00 (la ficha sale a las 13:00)
        $this->repo->saveCheckOut($idAsistencia, '10:00:00', 1, 'Salida Temprana');

        // afirmar: la salida quedo registrada con su estado
        $asistencia = $this->repo->findForDate($this->idAprendiz1, '2026-11-13');
        $this->assertSame('Salida Temprana', $asistencia['estado_asistencia']);
        $this->assertSame('10:00:00', $asistencia['hora_salida_registrada']);
    }

    public function testMarkAbsencesOnlyAfterTheFichaExitTime(): void
    {
        // preparar: Ana tiene asistencia del dia, Ben no tiene nada
        $this->repo->saveCheckIn($this->idAprendiz1, '2026-11-13', '07:00:00', 0, 'Normal', null);

        // actuar 1: a las 10:00 (antes de la salida de la ficha, 13:00)
        // nadie debe marcarse inasistencia todavia
        $marcados = $this->repo->markAbsences('2026-11-13', '2026-11-13', '10:00:00');
        $this->assertSame(0, $marcados);

        // actuar 2: a las 14:00 (ya paso la salida) -> Ben queda Inasistencia
        $marcados = $this->repo->markAbsences('2026-11-13', '2026-11-13', '14:00:00');
        $this->assertSame(1, $marcados);

        // afirmar: Ben tiene registro Inasistencia sin horas; Ana sigue intacto
        $ben = $this->repo->findForDate($this->idAprendiz2, '2026-11-13');
        $this->assertSame('Inasistencia', $ben['estado_asistencia']);
        $this->assertNull($ben['hora_entrada_registrada']);
        $ana = $this->repo->findForDate($this->idAprendiz1, '2026-11-13');
        $this->assertSame('Normal', $ana['estado_asistencia']);
    }
}
