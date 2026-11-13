<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ScheduleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findLearnerFichaId(int $learnerId): ?int
    {
        $statement = $this->pdo->prepare("SELECT f.id_ficha
            FROM usuario_has_ficha uf
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE uf.id_aprendiz = ?
            ORDER BY f.codigo_ficha LIMIT 1");
        $statement->execute([$learnerId]);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function findFichasWithSchedule(): array
    {
        return $this->pdo->query("SELECT DISTINCT f.id_ficha, f.codigo_ficha, f.nombre_programa
            FROM ficha f
            INNER JOIN horario h ON h.id_ficha = f.id_ficha
            ORDER BY f.codigo_ficha")->fetchAll();
    }

    public function findFicha(int $fichaId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id_ficha, codigo_ficha, nombre_programa,
                hora_entrada, hora_salida
            FROM ficha WHERE id_ficha = ?");
        $statement->execute([$fichaId]);
        $ficha = $statement->fetch();
        return $ficha ?: null;
    }

    public function findRange(int $fichaId, ?string $instructorPrefix): array
    {
        $sql = "SELECT MIN(hora_inicio) AS hi, MAX(hora_fin) AS hf, MIN(fecha) AS fi, MAX(fecha) AS ff
            FROM horario WHERE id_ficha = ?";
        $parameters = [$fichaId];
        if ($instructorPrefix !== null) {
            $sql .= ' AND (instructor LIKE ? OR es_festivo = 1)';
            $parameters[] = $instructorPrefix . '%';
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetch();
    }

    public function findWeekRows(
        int $fichaId,
        string $startDate,
        string $endDate,
        ?string $instructorPrefix
    ): array {
        $sql = "SELECT fecha, hora_inicio, instructor, competencia, es_festivo
            FROM horario
            WHERE id_ficha = ? AND fecha BETWEEN ? AND ?";
        $parameters = [$fichaId, $startDate, $endDate];
        if ($instructorPrefix !== null) {
            $sql .= ' AND (instructor LIKE ? OR es_festivo = 1)';
            $parameters[] = $instructorPrefix . '%';
        }
        $sql .= ' ORDER BY fecha, hora_inicio';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}
