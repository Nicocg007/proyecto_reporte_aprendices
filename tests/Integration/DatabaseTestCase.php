<?php

use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->pdo = new PDO(
                'mysql:host=localhost;dbname=reporte_asistencia_test;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            self::markTestSkipped(
                'No se pudo conectar a la base de pruebas reporte_asistencia_test en localhost: '
                . $exception->getMessage()
            );
        }

        $databaseName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!is_string($databaseName) || substr($databaseName, -5) !== '_test') {
            throw new RuntimeException(
                'Conexión rechazada: la base seleccionada debe terminar en "_test".'
            );
        }

        if (!$this->pdo->beginTransaction()) {
            throw new RuntimeException('No se pudo iniciar una transacción en la base de pruebas.');
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }
}
