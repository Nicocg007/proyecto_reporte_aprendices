<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class FichaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findActiveInstructors(): array
    {
        return $this->pdo->query("SELECT id_usuario, nombre, apellido FROM usuario
            WHERE id_rol = 2 AND estado = 'Activo'
            ORDER BY nombre")->fetchAll();
    }

    public function create(array $data): void
    {
        $statement = $this->pdo->prepare("INSERT INTO ficha
            (codigo_ficha, nombre_programa, hora_entrada, hora_salida, id_instructor_encargado)
            VALUES (?, ?, ?, ?, ?)");
        $statement->execute([
            $data['codigo_ficha'],
            $data['nombre_programa'],
            $data['hora_entrada'],
            $data['hora_salida'],
            $data['id_instructor'],
        ]);
    }

    public function findAllWithLearnerCounts(): array
    {
        return $this->pdo->query("SELECT f.codigo_ficha, f.nombre_programa, f.hora_entrada,
                f.hora_salida, CONCAT(u.nombre, ' ', u.apellido) AS instructor,
                COUNT(uf.id_aprendiz) AS total_aprendices
            FROM ficha f
            INNER JOIN usuario u ON u.id_usuario = f.id_instructor_encargado
            LEFT JOIN usuario_has_ficha uf ON uf.id_ficha = f.id_ficha
            GROUP BY f.id_ficha
            ORDER BY f.codigo_ficha")->fetchAll();
    }

    public function findLearnerFicha(int $learnerId): ?array
    {
        $statement = $this->pdo->prepare("SELECT f.codigo_ficha, f.nombre_programa,
                f.hora_entrada, f.hora_salida,
                CONCAT(ui.nombre, ' ', ui.apellido) AS instructor_nombre,
                ui.correo AS instructor_correo,
                (SELECT COUNT(*) FROM usuario_has_ficha WHERE id_ficha = f.id_ficha)
                    AS total_aprendices_ficha
            FROM usuario_has_ficha uf
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            INNER JOIN usuario ui ON ui.id_usuario = f.id_instructor_encargado
            WHERE uf.id_aprendiz = ?");
        $statement->execute([$learnerId]);
        $ficha = $statement->fetch();
        return $ficha ?: null;
    }

    public function findInstructorFichasWithLearnerCounts(int $instructorId): array
    {
        $statement = $this->pdo->prepare("SELECT f.codigo_ficha, f.nombre_programa,
                f.hora_entrada, f.hora_salida,
                (SELECT COUNT(*) FROM usuario_has_ficha WHERE id_ficha = f.id_ficha)
                    AS total_aprendices
            FROM ficha f WHERE f.id_instructor_encargado = ?
            ORDER BY f.codigo_ficha");
        $statement->execute([$instructorId]);
        return $statement->fetchAll();
    }
}
