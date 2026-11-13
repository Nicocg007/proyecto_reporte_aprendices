<?php

require_once __DIR__ . '/DatabaseTestCase.php';

final class DatabaseSmokeTest extends DatabaseTestCase
{
    public function testCanInsertAndReadRecord(): void
    {
        try {
            $this->pdo->exec(
                'CREATE TEMPORARY TABLE integration_smoke (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    value_text VARCHAR(100) NOT NULL
                ) ENGINE=InnoDB'
            );

            $insert = $this->pdo->prepare(
                'INSERT INTO integration_smoke (value_text) VALUES (:value_text)'
            );
            $insert->execute(['value_text' => 'smoke-test-record']);
            $id = (int) $this->pdo->lastInsertId();

            $select = $this->pdo->prepare(
                'SELECT value_text FROM integration_smoke WHERE id = :id'
            );
            $select->execute(['id' => $id]);

            self::assertSame('smoke-test-record', $select->fetchColumn());
        } finally {
            $this->pdo->exec('DROP TEMPORARY TABLE IF EXISTS integration_smoke');
        }
    }
}
