<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class AttendanceRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function markAbsences(string $date, string $today, string $currentTime): int
    {
        $sql = "SELECT a.id_usuario, f.hora_salida
            FROM usuario_has_ficha uf
            INNER JOIN usuario a ON a.id_usuario = uf.id_aprendiz AND a.id_rol = 3 AND a.estado = 'Activo'
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE NOT EXISTS (
                SELECT 1 FROM ingreso i WHERE i.id_aprendiz = a.id_usuario AND i.fecha = ?
            )";
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$date]);
        $candidates = $statement->fetchAll();
        $marked = 0;

        foreach ($candidates as $candidate) {
            if ($date === $today && $currentTime <= $candidate['hora_salida']) {
                continue;
            }

            $statement = $this->pdo->prepare(
                'SELECT COUNT(*) AS total FROM ingreso WHERE id_aprendiz = ? AND fecha = ?'
            );
            $statement->execute([$candidate['id_usuario'], $date]);
            if ($statement->fetch()['total'] > 0) {
                continue;
            }

            $statement = $this->pdo->prepare("INSERT INTO ingreso
                (id_aprendiz, fecha, hora_entrada_registrada, hora_salida_registrada, minutos_retardo, salida_temprana, estado_asistencia)
                VALUES (?, ?, NULL, NULL, 0, 0, 'Inasistencia')");
            $statement->execute([$candidate['id_usuario'], $date]);
            $marked++;
        }

        return $marked;
    }

    public function findByRfid(string $uid): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_usuario, nombre, apellido, estado FROM usuario WHERE rfid_uid = ?'
        );
        $statement->execute([$uid]);
        $learner = $statement->fetch();

        return $learner ?: null;
    }

    public function findScheduleForLearner(int $learnerId): ?array
    {
        $statement = $this->pdo->prepare("SELECT f.hora_entrada, f.hora_salida
            FROM usuario_has_ficha uf
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE uf.id_aprendiz = ?
            LIMIT 1");
        $statement->execute([$learnerId]);
        $schedule = $statement->fetch();

        return $schedule ?: null;
    }

    public function findForDate(int $learnerId, string $date): ?array
    {
        $statement = $this->pdo->prepare("SELECT id_ingreso, hora_entrada_registrada,
                hora_salida_registrada, estado_asistencia
            FROM ingreso WHERE id_aprendiz = ? AND fecha = ?");
        $statement->execute([$learnerId, $date]);
        $attendance = $statement->fetch();

        return $attendance ?: null;
    }

    public function findForToday(int $learnerId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id_ingreso, hora_entrada_registrada,
                hora_salida_registrada, estado_asistencia
            FROM ingreso WHERE id_aprendiz = ? AND fecha = CURDATE()");
        $statement->execute([$learnerId]);
        $attendance = $statement->fetch();

        return $attendance ?: null;
    }

    public function saveCheckIn(
        int $learnerId,
        ?string $date,
        string $time,
        int $lateMinutes,
        string $status,
        ?int $existingAttendanceId
    ): void {
        if ($existingAttendanceId !== null) {
            $statement = $this->pdo->prepare("UPDATE ingreso
                SET hora_entrada_registrada = ?, minutos_retardo = ?, estado_asistencia = ?
                WHERE id_ingreso = ?");
            $statement->execute([$time, $lateMinutes, $status, $existingAttendanceId]);
            return;
        }

        if ($date === null) {
            $statement = $this->pdo->prepare("INSERT INTO ingreso
                (id_aprendiz, fecha, hora_entrada_registrada, minutos_retardo, estado_asistencia)
                VALUES (?, CURDATE(), ?, ?, ?)");
            $statement->execute([$learnerId, $time, $lateMinutes, $status]);
            return;
        }

        $statement = $this->pdo->prepare("INSERT INTO ingreso
            (id_aprendiz, fecha, hora_entrada_registrada, minutos_retardo, estado_asistencia)
            VALUES (?, ?, ?, ?, ?)");
        $statement->execute([$learnerId, $date, $time, $lateMinutes, $status]);
    }

    public function saveCheckOut(
        int $attendanceId,
        string $time,
        int $earlyDeparture,
        string $status
    ): void {
        $statement = $this->pdo->prepare("UPDATE ingreso
            SET hora_salida_registrada = ?, salida_temprana = ?, estado_asistencia = ?
            WHERE id_ingreso = ?");
        $statement->execute([$time, $earlyDeparture, $status, $attendanceId]);
    }

    public function findReportRows(array $filters, string $role, int $userId): array
    {
        [$from, $where, $parameters] = $this->buildReportQuery($filters, $role, $userId);
        $sql = "SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.numero_documento AS documento,
            f.codigo_ficha AS ficha, i.fecha, i.hora_entrada_registrada AS entrada,
            i.hora_salida_registrada AS salida, i.minutos_retardo AS retardo, i.estado_asistencia AS estado"
            . $from . $where;
        $sql .= ' ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findReportPage(
        array $filters,
        string $role,
        int $userId,
        int $limit,
        int $offset
    ): array {
        [$from, $where, $parameters] = $this->buildReportQuery($filters, $role, $userId);

        $statement = $this->pdo->prepare('SELECT COUNT(*)' . $from . $where);
        $statement->execute($parameters);
        $totalRows = (int) $statement->fetchColumn();

        $sql = "SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.numero_documento AS documento,
            f.codigo_ficha AS ficha, i.fecha, i.hora_entrada_registrada AS entrada,
            i.hora_salida_registrada AS salida, i.minutos_retardo AS retardo, i.estado_asistencia AS estado"
            . $from . $where . "
            ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC
            LIMIT :limite OFFSET :offset";
        $statement = $this->pdo->prepare($sql);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limite', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();

        $statement = $this->pdo->prepare(
            'SELECT i.estado_asistencia AS estado, COUNT(*) AS total' . $from . $where
            . ' GROUP BY i.estado_asistencia'
        );
        $statement->execute($parameters);
        $statusCounts = [];
        foreach ($statement->fetchAll() as $row) {
            $statusCounts[$row['estado']] = (int) $row['total'];
        }

        return [
            'rows' => $rows,
            'total_rows' => $totalRows,
            'status_counts' => $statusCounts,
        ];
    }

    public function findFichasForReport(string $role, int $userId): array
    {
        if ($role === 'Instructor') {
            $statement = $this->pdo->prepare("SELECT id_ficha, codigo_ficha, nombre_programa
                FROM ficha WHERE id_instructor_encargado = ? ORDER BY codigo_ficha");
            $statement->execute([$userId]);
        } else {
            $statement = $this->pdo->query(
                'SELECT id_ficha, codigo_ficha, nombre_programa FROM ficha ORDER BY codigo_ficha'
            );
        }

        return $statement->fetchAll();
    }

    public function findInstructorFichas(int $instructorId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_ficha, codigo_ficha FROM ficha WHERE id_instructor_encargado = ? ORDER BY codigo_ficha'
        );
        $statement->execute([$instructorId]);
        return $statement->fetchAll();
    }

    public function findOwnedFicha(int $fichaId, int $instructorId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id_ficha, codigo_ficha, hora_entrada
            FROM ficha WHERE id_ficha = ? AND id_instructor_encargado = ?");
        $statement->execute([$fichaId, $instructorId]);
        $ficha = $statement->fetch();
        return $ficha ?: null;
    }

    public function saveManualAttendance(
        int $fichaId,
        int $instructorId,
        string $date,
        array $statuses,
        array $checkIns,
        array $checkOuts
    ): bool {
        $schedule = $this->findOwnedFicha($fichaId, $instructorId);
        if ($schedule === null || $date === '') {
            return false;
        }

        $service = new \App\Service\AttendanceService();
        foreach ($statuses as $learnerId => $status) {
            $checkIn = ($checkIns[$learnerId] ?? '') !== '' ? $checkIns[$learnerId] : null;
            $checkOut = ($checkOuts[$learnerId] ?? '') !== '' ? $checkOuts[$learnerId] : null;
            $calculation = $service->calculateManualAttendance(
                $checkIn,
                (string) $status,
                $schedule['hora_entrada']
            );

            $statement = $this->pdo->prepare(
                'SELECT id_ingreso FROM ingreso WHERE id_aprendiz = ? AND fecha = ?'
            );
            $statement->execute([$learnerId, $date]);
            $existing = $statement->fetch();

            if ($existing) {
                $statement = $this->pdo->prepare("UPDATE ingreso
                    SET hora_entrada_registrada = ?, hora_salida_registrada = ?, minutos_retardo = ?,
                        salida_temprana = ?, estado_asistencia = ?
                    WHERE id_ingreso = ?");
                $statement->execute([
                    $checkIn,
                    $checkOut,
                    $calculation['minutos_retardo'],
                    $calculation['salida_temprana'],
                    $calculation['estado'],
                    $existing['id_ingreso'],
                ]);
            } else {
                $statement = $this->pdo->prepare("INSERT INTO ingreso
                    (id_aprendiz, fecha, hora_entrada_registrada, hora_salida_registrada,
                        minutos_retardo, salida_temprana, estado_asistencia)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $statement->execute([
                    $learnerId,
                    $date,
                    $checkIn,
                    $checkOut,
                    $calculation['minutos_retardo'],
                    $calculation['salida_temprana'],
                    $calculation['estado'],
                ]);
            }
        }

        return true;
    }

    public function findLearnersForFicha(int $fichaId, string $date): array
    {
        $statement = $this->pdo->prepare("SELECT u.id_usuario, u.numero_documento, u.nombre, u.apellido,
                i.hora_entrada_registrada, i.hora_salida_registrada, i.estado_asistencia
            FROM usuario_has_ficha uf
            INNER JOIN usuario u ON u.id_usuario = uf.id_aprendiz
            LEFT JOIN ingreso i ON i.id_aprendiz = u.id_usuario AND i.fecha = ?
            WHERE uf.id_ficha = ? AND u.id_rol = 3
            ORDER BY u.nombre");
        $statement->execute([$date, $fichaId]);
        return $statement->fetchAll();
    }

    public function findInstructorAttendance(int $instructorId): array
    {
        $statement = $this->pdo->prepare("SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre,
                f.codigo_ficha AS ficha, i.fecha, i.hora_entrada_registrada AS entrada,
                i.hora_salida_registrada AS salida, i.estado_asistencia AS estado
            FROM ingreso i
            INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE f.id_instructor_encargado = ?
            ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC");
        $statement->execute([$instructorId]);
        return $statement->fetchAll();
    }

    public function findLearnerAttendance(int $learnerId): array
    {
        $statement = $this->pdo->prepare("SELECT fecha, hora_entrada_registrada AS entrada,
                hora_salida_registrada AS salida, minutos_retardo AS retardo,
                estado_asistencia AS estado
            FROM ingreso WHERE id_aprendiz = ?
            ORDER BY fecha DESC, hora_entrada_registrada DESC");
        $statement->execute([$learnerId]);
        return $statement->fetchAll();
    }

    public function findTodayAttendance(): array
    {
        return $this->pdo->query("SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre,
                i.hora_entrada_registrada AS entrada, i.hora_salida_registrada AS salida,
                i.minutos_retardo AS retardo, i.estado_asistencia AS estado
            FROM ingreso i
            INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
            WHERE i.fecha = CURDATE()
            ORDER BY i.hora_entrada_registrada DESC")->fetchAll();
    }

    private function buildReportQuery(array $filters, string $role, int $userId): array
    {
        $from = " FROM ingreso i
            INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha";
        $conditions = [];
        $parameters = [];

        if (($filters['fecha_inicio'] ?? '') !== '') {
            $conditions[] = 'i.fecha >= :fecha_inicio';
            $parameters[':fecha_inicio'] = $filters['fecha_inicio'];
        }
        if (($filters['fecha_fin'] ?? '') !== '') {
            $conditions[] = 'i.fecha <= :fecha_fin';
            $parameters[':fecha_fin'] = $filters['fecha_fin'];
        }
        if (($filters['ficha'] ?? '') !== '') {
            $conditions[] = 'uf.id_ficha = :ficha';
            $parameters[':ficha'] = (int) $filters['ficha'];
        }
        if (($filters['estado'] ?? '') !== '') {
            $conditions[] = 'i.estado_asistencia = :estado';
            $parameters[':estado'] = $filters['estado'];
        }
        if ($role === 'Aprendiz') {
            $conditions[] = 'i.id_aprendiz = :id_aprendiz';
            $parameters[':id_aprendiz'] = $userId;
        } elseif ($role === 'Instructor') {
            $conditions[] = 'f.id_instructor_encargado = :id_instructor';
            $parameters[':id_instructor'] = $userId;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        return [$from, $where, $parameters];
    }
}
