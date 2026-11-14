<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Base para pruebas de INTEGRACION contra SQLite en memoria.
 *
 * Que se integra aqui (lo real):
 *   - Repositorios de App\Repository\ ejecutando SQL real (INSERT/SELECT/UPDATE/JOIN)
 *   - El esquema de tablas del proyecto (database/sena_asistencia.sql)
 *
 * Que se simula (lo falso):
 *   - El motor: SQLite en memoria en lugar de MySQL. La logica y el SQL son los reales.
 *
 * Cada prueba arranca con una base vacia: setUp() reconstruye todo el esquema,
 * asi ningun test depende de los datos dejados por otro.
 */
abstract class SqliteIntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        // Pdo\Sqlite es un PDO de SQLite: permite crear BD en memoria
        // y registrar funciones personalizadas con la API moderna de PHP 8.5
        $this->pdo = new Pdo\Sqlite('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        // Esquema tomado de database/sena_asistencia.sql (adaptado a SQLite)
        $this->pdo->exec('CREATE TABLE roles (
            id_rol   INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre   TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE usuario (
            id_usuario        INTEGER PRIMARY KEY AUTOINCREMENT,
            numero_documento  TEXT NOT NULL UNIQUE,
            nombre            TEXT NOT NULL,
            apellido          TEXT NOT NULL,
            correo            TEXT,
            password          TEXT NOT NULL,
            rfid_uid          TEXT UNIQUE,
            id_rol            INTEGER NOT NULL REFERENCES roles(id_rol),
            estado            TEXT NOT NULL DEFAULT \'Activo\'
        )');

        $this->pdo->exec('CREATE TABLE ficha (
            id_ficha                INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo_ficha            TEXT NOT NULL UNIQUE,
            nombre_programa         TEXT NOT NULL,
            hora_entrada            TEXT NOT NULL,
            hora_salida             TEXT NOT NULL,
            id_instructor_encargado INTEGER NOT NULL REFERENCES usuario(id_usuario)
        )');

        $this->pdo->exec('CREATE TABLE usuario_has_ficha (
            id_aprendiz_ficha INTEGER PRIMARY KEY AUTOINCREMENT,
            id_aprendiz       INTEGER NOT NULL UNIQUE REFERENCES usuario(id_usuario),
            id_ficha          INTEGER NOT NULL REFERENCES ficha(id_ficha)
        )');

        $this->pdo->exec('CREATE TABLE ingreso (
            id_ingreso              INTEGER PRIMARY KEY AUTOINCREMENT,
            id_aprendiz             INTEGER NOT NULL REFERENCES usuario(id_usuario),
            fecha                   TEXT NOT NULL,
            hora_entrada_registrada TEXT,
            hora_salida_registrada  TEXT,
            minutos_retardo         INTEGER DEFAULT 0,
            salida_temprana         INTEGER DEFAULT 0,
            estado_asistencia       TEXT DEFAULT \'Normal\'
        )');

        $this->pdo->exec('CREATE TABLE excusa (
            id_excusa           INTEGER PRIMARY KEY AUTOINCREMENT,
            id_aprendiz         INTEGER NOT NULL REFERENCES usuario(id_usuario),
            id_ingreso          INTEGER REFERENCES ingreso(id_ingreso),
            fecha_inasistencia  TEXT NOT NULL,
            archivo_adjunto     TEXT NOT NULL,
            observacion         TEXT NOT NULL,
            estado              TEXT DEFAULT \'Pendiente\',
            id_instructor_revisor INTEGER REFERENCES usuario(id_usuario),
            fecha_revision      TEXT
        )');

        $this->pdo->exec('CREATE TABLE horario (
            id_horario   INTEGER PRIMARY KEY AUTOINCREMENT,
            id_ficha     INTEGER NOT NULL REFERENCES ficha(id_ficha),
            fecha        TEXT NOT NULL,
            hora_inicio  TEXT NOT NULL,
            hora_fin     TEXT NOT NULL,
            instructor   TEXT,
            competencia  TEXT,
            es_festivo   INTEGER NOT NULL DEFAULT 0
        )');

        // Funciones de MySQL que usan los repositorios, reimplementadas para SQLite.
        // Asi el SQL de produccion corre tal cual, sin modificarlo.
        $this->pdo->createFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        $this->pdo->createFunction('CURDATE', static fn (): string => date('Y-m-d'));
        $this->pdo->createFunction('CONCAT', static fn (string ...$partes): string => implode('', $partes));
    }

    /* ---------- Helpers de datos de prueba (el "preparar" de cada test) ---------- */

    protected function crearRol(string $nombre): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO roles (nombre) VALUES (?)');
        $stmt->execute([$nombre]);
        return (int) $this->pdo->lastInsertId();
    }

    protected function crearUsuario(
        string $documento,
        string $nombre,
        string $apellido,
        int $idRol,
        string $estado = 'Activo',
        ?string $rfid = null,
        string $passwordHash = 'hash-inicial'
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuario (numero_documento, nombre, apellido, correo, password, rfid_uid, id_rol, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $documento,
            $nombre,
            $apellido,
            $documento . '@example.test',
            $passwordHash,
            $rfid,
            $idRol,
            $estado,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    protected function crearFicha(
        string $codigo,
        string $programa,
        string $horaEntrada,
        string $horaSalida,
        int $idInstructor
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ficha (codigo_ficha, nombre_programa, hora_entrada, hora_salida, id_instructor_encargado)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$codigo, $programa, $horaEntrada, $horaSalida, $idInstructor]);
        return (int) $this->pdo->lastInsertId();
    }

    protected function asignarAprendizAFicha(int $idAprendiz, int $idFicha): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuario_has_ficha (id_aprendiz, id_ficha) VALUES (?, ?)'
        );
        $stmt->execute([$idAprendiz, $idFicha]);
    }
}
