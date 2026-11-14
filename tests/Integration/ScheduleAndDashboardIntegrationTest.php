<?php

declare(strict_types=1);

require_once __DIR__ . '/SqliteIntegrationTestCase.php';

use App\Repository\DashboardRepository;
use App\Repository\ScheduleRepository;

/**
 * Integracion: ScheduleRepository + DashboardRepository + PDO + SQL real
 * (horario, ficha, usuario, usuario_has_ficha, ingreso y excusa juntas).
 *
 * Reglas de negocio cubiertas:
 *   - El horario de la ficha se consulta por rango de fechas y por instructor
 *     (las jornadas festivas siempre se muestran, sea quien sea el instructor)
 *   - Los agregados MIN/MAX definen el rango de la semana de la ficha
 *   - El aprendiz se resuelve a su ficha para ver el calendario
 *   - El dashboard del admin cuenta aprendices, fichas, asistencias del dia
 *     y excusas pendientes del sistema
 *   - El dashboard del aprendiz resume sus estados: dias asistidos (sin
 *     contar inasistencias), retardos, inasistencias y excusas pendientes
 */
final class ScheduleAndDashboardIntegrationTest extends SqliteIntegrationTestCase
{
    private ScheduleRepository $horarios;
    private DashboardRepository $dashboards;
    private int $idFicha;
    private int $idAprendiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->horarios = new ScheduleRepository($this->pdo);
        $this->dashboards = new DashboardRepository($this->pdo);

        // escenario base: roles, instructor, ficha, 3 aprendices
        $this->crearRol('Administrador');
        $idRolInstructor = $this->crearRol('Instructor');
        $this->crearRol('Aprendiz');

        $idInstructor = $this->crearUsuario('INS-1', 'Guia', 'Sena', 2);
        $this->idFicha = $this->crearFicha('3064749', 'ADSO', '07:00:00', '13:00:00', $idInstructor);
        $this->idAprendiz = $this->crearUsuario('APR-1', 'Ana', 'Uno', 3);
        $this->asignarAprendizAFicha($this->idAprendiz, $this->idFicha);
        $this->crearUsuario('APR-2', 'Ben', 'Dos', 3);
        $this->crearUsuario('APR-3', 'Carl', 'Tres', 3);
    }

    private function insertarHorario(
        string $fecha,
        string $inicio,
        string $fin,
        string $instructor,
        string $competencia,
        int $esFestivo = 0
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO horario (id_ficha, fecha, hora_inicio, hora_fin, instructor, competencia, es_festivo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$this->idFicha, $fecha, $inicio, $fin, $instructor, $competencia, $esFestivo]);
    }

    public function testWeekRowsReturnOnlyDatesInRangeOrdered(): void
    {
        // preparar: 3 jornadas, una de ellas fuera del rango pedido
        $this->insertarHorario('2026-11-09', '07:00:00', '11:00:00', 'Dr. Lopez', 'POO');
        $this->insertarHorario('2026-11-10', '08:00:00', '12:00:00', 'Dr. Lopez', 'BD');
        $this->insertarHorario('2026-11-16', '07:00:00', '11:00:00', 'Dr. Lopez', 'POO');

        // actuar: pedir solo la semana del 09 al 13
        $filas = $this->horarios->findWeekRows($this->idFicha, '2026-11-09', '2026-11-13', null);

        // afirmar: solo 2 jornadas, ordenadas por fecha y hora
        $this->assertCount(2, $filas);
        $this->assertSame('2026-11-09', $filas[0]['fecha']);
        $this->assertSame('2026-11-10', $filas[1]['fecha']);
    }

    public function testRangeAggregatesHoursAndKeepsHolidaysOfAnyInstructor(): void
    {
        // preparar: 2 jornadas del instructor Lopez y 1 festivo de otro instructor
        $this->insertarHorario('2026-11-09', '07:00:00', '11:00:00', 'Dr. Lopez', 'POO');
        $this->insertarHorario('2026-11-12', '08:00:00', '12:00:00', 'Dr. Lopez', 'BD');
        $this->insertarHorario('2026-11-13', '09:00:00', '10:00:00', 'Dra. Ruiz', 'Cultura fisica', 1);

        // actuar + afirmar: el rango agregado de la ficha
        $rango = $this->horarios->findRange($this->idFicha, null);
        $this->assertSame('07:00:00', $rango['hi']);
        $this->assertSame('12:00:00', $rango['hf']);
        $this->assertSame('2026-11-09', $rango['fi']);
        $this->assertSame('2026-11-13', $rango['ff']);

        // filtrando por prefijo de instructor: las jornadas festivas se mantienen
        // aunque el instructor no coincida (regla de visualizacion del calendario)
        $rangoLopez = $this->horarios->findRange($this->idFicha, 'Dr. L');
        $this->assertSame('2026-11-13', $rangoLopez['ff']);
    }

    public function testLearnerResolvesToHisFichaAndUnassignedGetsNull(): void
    {
        // actuar + afirmar: Ana resuelve a su ficha; un aprendiz sin ficha, no
        $this->assertSame($this->idFicha, $this->horarios->findLearnerFichaId($this->idAprendiz));
        $sinFicha = $this->crearUsuario('APR-9', 'Sin', 'Ficha', 3);
        $this->assertNull($this->horarios->findLearnerFichaId($sinFicha));
    }

    public function testAdminDashboardCountsTheWholeScenario(): void
    {
        // preparar: 1 asistencia de HOY y 1 excusa pendiente
        $stmt = $this->pdo->prepare(
            "INSERT INTO ingreso (id_aprendiz, fecha, hora_entrada_registrada, minutos_retardo, estado_asistencia)
             VALUES (?, ?, '07:00:00', 0, 'Normal')"
        );
        $stmt->execute([$this->idAprendiz, date('Y-m-d')]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO excusa (id_aprendiz, fecha_inasistencia, archivo_adjunto, observacion)
             VALUES (?, '2026-11-10', 'excusa.pdf', 'Cita medica')"
        );
        $stmt->execute([$this->idAprendiz]);

        // actuar: dashboard del administrador
        $panel = $this->dashboards->findAdminDashboard();

        // afirmar: los conteos del sistema completo
        $this->assertSame(3, $panel['total_aprendices']);
        $this->assertSame(1, $panel['total_fichas']);
        $this->assertSame(1, $panel['asistencias_hoy']);
        $this->assertSame(1, $panel['excusas_pendientes']);
        $this->assertCount(1, $panel['ultimas_asistencias']);
        $this->assertSame('Ana', $panel['ultimas_asistencias'][0]['nombre']);
    }

    public function testLearnerDashboardSummarizesHisStates(): void
    {
        // preparar: historial del aprendiz: 1 Normal, 1 Retardo, 1 Inasistencia
        $stmt = $this->pdo->prepare(
            "INSERT INTO ingreso (id_aprendiz, fecha, hora_entrada_registrada, minutos_retardo, estado_asistencia)
             VALUES (?, '2026-11-09', '07:00:00', 0, 'Normal')"
        );
        $stmt->execute([$this->idAprendiz]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO ingreso (id_aprendiz, fecha, hora_entrada_registrada, minutos_retardo, estado_asistencia)
             VALUES (?, '2026-11-10', '07:15:00', 15, 'Retardo')"
        );
        $stmt->execute([$this->idAprendiz]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO ingreso (id_aprendiz, fecha, estado_asistencia)
             VALUES (?, '2026-11-11', 'Inasistencia')"
        );
        $stmt->execute([$this->idAprendiz]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO excusa (id_aprendiz, fecha_inasistencia, archivo_adjunto, observacion)
             VALUES (?, '2026-11-11', 'excusa.pdf', 'Por la inasistencia')"
        );
        $stmt->execute([$this->idAprendiz]);

        // actuar: dashboard del aprendiz
        $panel = $this->dashboards->findLearnerDashboard($this->idAprendiz);

        // afirmar: dias asistidos NO cuenta inasistencias (regla de negocio)
        $this->assertSame(2, (int) $panel['dias_asistidos']);
        $this->assertSame(1, (int) $panel['total_retardos']);
        $this->assertSame(1, (int) $panel['total_inasistencias']);
        $this->assertSame(1, (int) $panel['excusas_pendientes']);
        $this->assertCount(3, $panel['mi_historial']);
        $this->assertCount(1, $panel['mis_excusas']);
    }
}
