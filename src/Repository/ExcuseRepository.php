<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ExcuseRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $learnerId, string $date, string $attachment, string $reason): void
    {
        $statement = $this->pdo->prepare("INSERT INTO excusa
            (id_aprendiz, fecha_inasistencia, archivo_adjunto, observacion, estado)
            VALUES (?, ?, ?, ?, 'Pendiente')");
        $statement->execute([$learnerId, $date, $attachment, $reason]);
    }

    public function canInstructorReview(int $excuseId, int $instructorId): bool
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) AS total FROM excusa e
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE e.id_excusa = ? AND f.id_instructor_encargado = ?");
        $statement->execute([$excuseId, $instructorId]);

        return (int) $statement->fetch()['total'] > 0;
    }

    public function review(int $excuseId, int $reviewerId, string $newStatus): bool
    {
        $statement = $this->pdo->prepare("UPDATE excusa
            SET estado = ?, id_instructor_revisor = ?, fecha_revision = NOW()
            WHERE id_excusa = ? AND estado = 'Pendiente'");
        $statement->execute([$newStatus, $reviewerId, $excuseId]);

        if ($statement->rowCount() === 0) {
            return false;
        }

        if ($newStatus === 'Aprobada') {
            $statement = $this->pdo->prepare(
                'SELECT id_aprendiz, fecha_inasistencia FROM excusa WHERE id_excusa = ?'
            );
            $statement->execute([$excuseId]);
            $excuse = $statement->fetch();
            if ($excuse) {
                $statement = $this->pdo->prepare(
                    'SELECT id_ingreso FROM ingreso WHERE id_aprendiz = ? AND fecha = ?'
                );
                $statement->execute([$excuse['id_aprendiz'], $excuse['fecha_inasistencia']]);
                $attendance = $statement->fetch();
                if ($attendance) {
                    $statement = $this->pdo->prepare("UPDATE ingreso
                        SET estado_asistencia = 'Normal', minutos_retardo = 0
                        WHERE id_ingreso = ?");
                    $statement->execute([$attendance['id_ingreso']]);
                    $statement = $this->pdo->prepare(
                        'UPDATE excusa SET id_ingreso = ? WHERE id_excusa = ?'
                    );
                    $statement->execute([$attendance['id_ingreso'], $excuseId]);
                }
            }
        }

        return true;
    }

    public function findNotificationCounts(int $userId, string $role): array
    {
        if ($role === 'Administrador') {
            $statement = $this->pdo->query(
                "SELECT COUNT(*) AS total FROM excusa WHERE estado = 'Pendiente'"
            );
            return ['Pendiente' => (int) $statement->fetch()['total']];
        }

        if ($role === 'Instructor') {
            $statement = $this->pdo->prepare("SELECT COUNT(*) AS total FROM excusa e
                INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
                INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
                WHERE e.estado = 'Pendiente' AND f.id_instructor_encargado = ?");
            $statement->execute([$userId]);
            return ['Pendiente' => (int) $statement->fetch()['total']];
        }

        $statement = $this->pdo->prepare("SELECT estado, COUNT(*) AS total
            FROM excusa WHERE id_aprendiz = ? GROUP BY estado");
        $statement->execute([$userId]);
        $counts = ['Pendiente' => 0, 'Aprobada' => 0, 'Rechazada' => 0];
        foreach ($statement->fetchAll() as $row) {
            $counts[$row['estado']] = (int) $row['total'];
        }
        return $counts;
    }

    public function findAdminLists(): array
    {
        $pendingCount = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM excusa WHERE estado = 'Pendiente'"
        )->fetchColumn();
        $pending = $this->pdo->query("SELECT e.id_excusa AS id,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre_aprendiz,
                e.fecha_inasistencia AS fecha, e.observacion AS motivo, e.archivo_adjunto,
                CONCAT(ui.nombre, ' ', ui.apellido) AS instructor
            FROM excusa e
            INNER JOIN usuario u ON u.id_usuario = e.id_aprendiz
            LEFT JOIN usuario ui ON ui.id_usuario = e.id_instructor_revisor
            WHERE e.estado = 'Pendiente'
            ORDER BY e.fecha_inasistencia ASC")->fetchAll();
        $reviewed = $this->pdo->query("SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre_aprendiz,
                e.fecha_inasistencia AS fecha, e.observacion AS motivo, e.estado,
                e.archivo_adjunto, e.fecha_revision
            FROM excusa e
            INNER JOIN usuario u ON u.id_usuario = e.id_aprendiz
            WHERE e.estado <> 'Pendiente'
            ORDER BY e.fecha_revision DESC")->fetchAll();

        return [
            'pending_count' => $pendingCount,
            'pending' => $pending,
            'reviewed' => $reviewed,
        ];
    }

    public function findInstructorLists(int $instructorId): array
    {
        $scope = " FROM excusa e
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE f.id_instructor_encargado = ?";
        $pendingCount = $this->pdo->prepare(
            "SELECT COUNT(*)" . $scope . " AND e.estado = 'Pendiente'"
        );
        $pendingCount->execute([$instructorId]);
        $pending = $this->pdo->prepare("SELECT e.id_excusa AS id,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre_aprendiz,
                e.fecha_inasistencia AS fecha, e.observacion AS motivo, e.archivo_adjunto
            " . $scope . " AND e.estado = 'Pendiente'
            ORDER BY e.fecha_inasistencia ASC");
        $pending->execute([$instructorId]);
        $reviewed = $this->pdo->prepare("SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre_aprendiz,
                e.fecha_inasistencia AS fecha, e.observacion AS motivo, e.estado,
                e.archivo_adjunto, e.fecha_revision
            " . $scope . " AND e.estado <> 'Pendiente'
            ORDER BY e.fecha_revision DESC");
        $reviewed->execute([$instructorId]);

        return [
            'pending_count' => $pendingCount->fetchColumn(),
            'pending' => $pending->fetchAll(),
            'reviewed' => $reviewed->fetchAll(),
        ];
    }

    public function findLearnerExcuses(int $learnerId): array
    {
        $statement = $this->pdo->prepare("SELECT fecha_inasistencia AS fecha,
                observacion AS motivo, estado, archivo_adjunto, fecha_revision
            FROM excusa WHERE id_aprendiz = ?
            ORDER BY fecha_inasistencia DESC");
        $statement->execute([$learnerId]);
        return $statement->fetchAll();
    }
}
