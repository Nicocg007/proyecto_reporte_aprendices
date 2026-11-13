<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findForLogin(string $document): ?array
    {
        $statement = $this->pdo->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.password,
                u.estado, r.nombre AS rol
            FROM usuario u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.numero_documento = :documento");
        $statement->execute([':documento' => $document]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByDocument(string $document): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_usuario, numero_documento, nombre, correo FROM usuario WHERE numero_documento = ?'
        );
        $statement->execute([$document]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function updatePasswordByDocument(string $document, string $passwordHash): void
    {
        $statement = $this->pdo->prepare('UPDATE usuario SET password = ? WHERE numero_documento = ?');
        $statement->execute([$passwordHash, $document]);
    }

    public function findProfile(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT numero_documento, nombre, apellido, correo FROM usuario WHERE id_usuario = ?'
        );
        $statement->execute([$userId]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }

    public function findPasswordHash(int $userId): ?string
    {
        $statement = $this->pdo->prepare('SELECT password FROM usuario WHERE id_usuario = ?');
        $statement->execute([$userId]);
        $hash = $statement->fetchColumn();

        return $hash === false ? null : $hash;
    }

    public function updateEmail(int $userId, string $email): void
    {
        $statement = $this->pdo->prepare('UPDATE usuario SET correo = ? WHERE id_usuario = ?');
        $statement->execute([$email, $userId]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $statement = $this->pdo->prepare('UPDATE usuario SET password = ? WHERE id_usuario = ?');
        $statement->execute([$passwordHash, $userId]);
    }

    public function findFichas(): array
    {
        return $this->pdo->query(
            'SELECT id_ficha, codigo_ficha FROM ficha ORDER BY codigo_ficha'
        )->fetchAll();
    }

    public function findLearners(array $filters): array
    {
        $sql = "SELECT u.id_usuario AS id, u.numero_documento AS documento,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.correo,
                f.codigo_ficha AS ficha, uf.id_ficha AS id_ficha,
                u.rfid_uid AS rfid, u.estado
            FROM usuario u
            LEFT JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
            LEFT JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE u.id_rol = 3";
        $parameters = [];
        if (($filters['buscar'] ?? '') !== '') {
            $sql .= ' AND (u.numero_documento LIKE :buscar OR u.nombre LIKE :buscar)';
            $parameters[':buscar'] = '%' . $filters['buscar'] . '%';
        }
        if (($filters['ficha'] ?? '') !== '') {
            $sql .= ' AND uf.id_ficha = :ficha';
            $parameters[':ficha'] = (int) $filters['ficha'];
        }
        if (($filters['estado'] ?? '') !== '') {
            $sql .= ' AND u.estado = :estado';
            $parameters[':estado'] = $filters['estado'];
        }
        $sql .= ' ORDER BY u.nombre';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function findLearner(int $learnerId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id_usuario, numero_documento, nombre, apellido,
                correo, rfid_uid, estado
            FROM usuario WHERE id_usuario = ? AND id_rol = 3");
        $statement->execute([$learnerId]);
        $learner = $statement->fetch();
        if (!$learner) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id_ficha FROM usuario_has_ficha WHERE id_aprendiz = ? LIMIT 1'
        );
        $statement->execute([$learnerId]);
        $learner['id_ficha'] = $statement->fetchColumn();
        return $learner;
    }

    public function updateLearner(array $learner, ?string $passwordHash): void
    {
        $statement = $this->pdo->prepare("UPDATE usuario
            SET numero_documento = ?, nombre = ?, apellido = ?, correo = ?, rfid_uid = ?, estado = ?
            WHERE id_usuario = ?");
        $statement->execute([
            $learner['documento'],
            $learner['nombre'],
            $learner['apellido'],
            $learner['correo'],
            $learner['rfid'],
            $learner['estado'],
            $learner['id'],
        ]);
        if ($passwordHash !== null) {
            $this->updatePassword((int) $learner['id'], $passwordHash);
        }
        $statement = $this->pdo->prepare(
            'DELETE FROM usuario_has_ficha WHERE id_aprendiz = ?'
        );
        $statement->execute([$learner['id']]);
        if ($learner['ficha'] !== '') {
            $statement = $this->pdo->prepare(
                'INSERT INTO usuario_has_ficha (id_aprendiz, id_ficha) VALUES (?, ?)'
            );
            $statement->execute([$learner['id'], $learner['ficha']]);
        }
    }

    public function createLearner(array $learner, string $passwordHash): void
    {
        $statement = $this->pdo->prepare("INSERT INTO usuario
            (numero_documento, nombre, apellido, correo, password, rfid_uid, id_rol, estado)
            VALUES (?, ?, ?, ?, ?, ?, 3, 'Activo')");
        $statement->execute([
            $learner['documento'],
            $learner['nombre'],
            $learner['apellido'],
            $learner['correo'],
            $passwordHash,
            $learner['rfid'],
        ]);

        $learnerId = $this->pdo->lastInsertId();
        if ($learner['ficha'] !== '') {
            $statement = $this->pdo->prepare(
                'INSERT INTO usuario_has_ficha (id_aprendiz, id_ficha) VALUES (?, ?)'
            );
            $statement->execute([$learnerId, $learner['ficha']]);
        }
    }

    public function updateInstructor(array $instructor, ?string $passwordHash): void
    {
        $statement = $this->pdo->prepare("UPDATE usuario
            SET numero_documento = ?, nombre = ?, apellido = ?, correo = ?, rfid_uid = ?, estado = ?
            WHERE id_usuario = ? AND id_rol = 2");
        $statement->execute([
            $instructor['documento'],
            $instructor['nombre'],
            $instructor['apellido'],
            $instructor['correo'],
            $instructor['rfid'],
            $instructor['estado'],
            $instructor['id'],
        ]);
        if ($passwordHash !== null) {
            $this->updatePassword((int) $instructor['id'], $passwordHash);
        }
    }

    public function createInstructor(array $instructor, string $passwordHash): void
    {
        $statement = $this->pdo->prepare("INSERT INTO usuario
            (numero_documento, nombre, apellido, correo, password, rfid_uid, id_rol, estado)
            VALUES (?, ?, ?, ?, ?, ?, 2, 'Activo')");
        $statement->execute([
            $instructor['documento'],
            $instructor['nombre'],
            $instructor['apellido'],
            $instructor['correo'],
            $passwordHash,
            $instructor['rfid'],
        ]);
    }

    public function findInstructors(): array
    {
        return $this->pdo->query("SELECT id_usuario AS id, numero_documento, nombre, apellido,
                correo, rfid_uid, estado
            FROM usuario WHERE id_rol = 2 ORDER BY nombre")->fetchAll();
    }

    public function findInstructor(int $instructorId): ?array
    {
        $statement = $this->pdo->prepare("SELECT id_usuario, numero_documento, nombre, apellido,
                correo, rfid_uid, estado
            FROM usuario WHERE id_usuario = ? AND id_rol = 2");
        $statement->execute([$instructorId]);
        $instructor = $statement->fetch();
        return $instructor ?: null;
    }
}
