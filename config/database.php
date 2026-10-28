<?php

require_once __DIR__ . '/env_loader.php';

// clase database para crear la conexion con mysql
class Database {

    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    // toma los datos desde config/env.php (no versionado)
    public function __construct() {
        $env = cargarEnv();
        $this->host = $env['db_host'] ?? '';
        $this->db_name = $env['db_name'] ?? '';
        $this->username = $env['db_user'] ?? '';
        $this->password = $env['db_pass'] ?? '';
    }

    // metodo que devuelve la conexion lista para usar
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            // mensaje generico: no filtrar detalles del driver/host
            die('Error de conexion a la base de datos.');
        }

        return $this->conn;
    }
}
