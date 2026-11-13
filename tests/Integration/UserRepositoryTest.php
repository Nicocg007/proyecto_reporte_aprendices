<?php

use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    public function testRepositoryUsesInjectedPdoForLoginLookupAndProfileUpdate(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE roles (id_rol INTEGER PRIMARY KEY, nombre TEXT NOT NULL)');
        $pdo->exec("CREATE TABLE usuario (
            id_usuario INTEGER PRIMARY KEY,
            nombre TEXT NOT NULL,
            apellido TEXT NOT NULL,
            correo TEXT NOT NULL,
            password TEXT NOT NULL,
            estado TEXT NOT NULL,
            id_rol INTEGER NOT NULL,
            numero_documento TEXT NOT NULL
        )");
        $pdo->exec("INSERT INTO roles VALUES (1, 'Administrador')");
        $pdo->exec("INSERT INTO usuario VALUES (
            4, 'Ada', 'Lovelace', 'ada@example.test', 'hash', 'Activo', 1, 'DOC-4'
        )");
        $repository = new UserRepository($pdo);

        self::assertSame('Administrador', $repository->findForLogin('DOC-4')['rol']);
        self::assertNull($repository->findForLogin("' OR 1=1 --"));

        $repository->updateEmail(4, 'ada+updated@example.test');
        self::assertSame(
            'ada+updated@example.test',
            $repository->findProfile(4)['correo']
        );
    }
}
