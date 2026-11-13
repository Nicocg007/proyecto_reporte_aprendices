<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class DashboardRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAdminDashboard(): array
    {
        return [
            'total_aprendices' => $this->count('SELECT COUNT(*) FROM usuario WHERE id_rol = 3'),
            'total_fichas' => $this->count('SELECT COUNT(*) FROM ficha'),
            'asistencias_hoy' => $this->count('SELECT COUNT(*) FROM ingreso WHERE fecha = CURDATE()'),
            'excusas_pendientes' => $this->count(
                "SELECT COUNT(*) FROM excusa WHERE estado = 'Pendiente'"
            ),
            'ultimas_asistencias' => $this->pdo->query("SELECT u.nombre, f.codigo_ficha AS ficha,
                    i.hora_entrada_registrada AS entrada, i.estado_asistencia AS estado
                FROM ingreso i
                INNER JOIN usuario u ON i.id_aprendiz = u.id_usuario
                INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
                INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
                ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC
                LIMIT 5")->fetchAll(),
        ];
    }

    public function findInstructorDashboard(int $instructorId): array
    {
        $learnerCount = $this->pdo->prepare("SELECT COUNT(DISTINCT uf.id_aprendiz) AS total
            FROM usuario_has_ficha uf
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE f.id_instructor_encargado = ?");
        $learnerCount->execute([$instructorId]);

        $attendanceCount = $this->pdo->prepare("SELECT COUNT(*) AS total
            FROM ingreso i
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = i.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE i.fecha = CURDATE() AND f.id_instructor_encargado = ?");
        $attendanceCount->execute([$instructorId]);

        $lateCount = $this->pdo->prepare("SELECT COUNT(*) AS total
            FROM ingreso i
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = i.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE i.fecha = CURDATE() AND i.estado_asistencia = 'Retardo'
                AND f.id_instructor_encargado = ?");
        $lateCount->execute([$instructorId]);

        $excuseCount = $this->pdo->prepare("SELECT COUNT(*) AS total
            FROM excusa e
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE e.estado = 'Pendiente' AND f.id_instructor_encargado = ?");
        $excuseCount->execute([$instructorId]);

        $todayAttendance = $this->pdo->prepare("SELECT u.nombre, f.codigo_ficha AS ficha,
                i.hora_entrada_registrada AS entrada, i.estado_asistencia AS estado
            FROM ingreso i
            INNER JOIN usuario u ON i.id_aprendiz = u.id_usuario
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE i.fecha = CURDATE() AND f.id_instructor_encargado = ?
            ORDER BY i.hora_entrada_registrada DESC
            LIMIT 8");
        $todayAttendance->execute([$instructorId]);

        $pendingExcuses = $this->pdo->prepare("SELECT u.nombre AS nombre_aprendiz,
                e.fecha_inasistencia AS fecha, e.observacion AS motivo
            FROM excusa e
            INNER JOIN usuario u ON e.id_aprendiz = u.id_usuario
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE e.estado = 'Pendiente' AND f.id_instructor_encargado = ?
            ORDER BY e.fecha_inasistencia ASC");
        $pendingExcuses->execute([$instructorId]);

        return [
            'mis_aprendices' => $learnerCount->fetch()['total'],
            'asistencias_hoy' => $attendanceCount->fetch()['total'],
            'retardos_hoy' => $lateCount->fetch()['total'],
            'excusas_pendientes' => $excuseCount->fetch()['total'],
            'asistencia_hoy' => $todayAttendance->fetchAll(),
            'excusas_por_revisar' => $pendingExcuses->fetchAll(),
        ];
    }

    public function findLearnerDashboard(int $learnerId): array
    {
        $attendanceCount = $this->pdo->prepare("SELECT COUNT(*) FROM ingreso
            WHERE id_aprendiz = ? AND estado_asistencia IN ('Normal','Retardo','Salida Temprana')");
        $attendanceCount->execute([$learnerId]);

        $lateCount = $this->pdo->prepare("SELECT COUNT(*) FROM ingreso
            WHERE id_aprendiz = ? AND estado_asistencia = 'Retardo'");
        $lateCount->execute([$learnerId]);

        $absenceCount = $this->pdo->prepare("SELECT COUNT(*) FROM ingreso
            WHERE id_aprendiz = ? AND estado_asistencia = 'Inasistencia'");
        $absenceCount->execute([$learnerId]);

        $excuseCount = $this->pdo->prepare("SELECT COUNT(*) FROM excusa
            WHERE id_aprendiz = ? AND estado = 'Pendiente'");
        $excuseCount->execute([$learnerId]);

        $history = $this->pdo->prepare("SELECT fecha, hora_entrada_registrada AS entrada,
                hora_salida_registrada AS salida, estado_asistencia AS estado
            FROM ingreso WHERE id_aprendiz = ?
            ORDER BY fecha DESC, hora_entrada_registrada DESC LIMIT 10");
        $history->execute([$learnerId]);

        $excuses = $this->pdo->prepare("SELECT fecha_inasistencia AS fecha, observacion AS motivo,
                estado, fecha_revision
            FROM excusa WHERE id_aprendiz = ? ORDER BY fecha_inasistencia DESC");
        $excuses->execute([$learnerId]);

        return [
            'dias_asistidos' => $attendanceCount->fetchColumn(),
            'total_retardos' => $lateCount->fetchColumn(),
            'total_inasistencias' => $absenceCount->fetchColumn(),
            'excusas_pendientes' => $excuseCount->fetchColumn(),
            'mi_historial' => $history->fetchAll(),
            'mis_excusas' => $excuses->fetchAll(),
        ];
    }

    private function count(string $query): int
    {
        return (int) $this->pdo->query($query)->fetchColumn();
    }
}
