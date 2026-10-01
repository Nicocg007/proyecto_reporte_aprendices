<?php

// clase database para crear la conexion con mysql
class Database {

    // datos de acceso a la base de datos
    private $host = 'localhost';
    private $db_name = 'sena_asistencia'; // nombre de la base de datos
    private $username = 'root'; // usuario de phpmyadmin
    private $password = ''; // contraseña de usuario de phpmyadmin
    private $conn; // conexion cuando ya exista

    // metodo que devuelve la conexion lista para usar
    public function getConnection() {
        $this->conn = null; // inicia la conexion en null si esta vacia

        try {
            // PDO = forma segura de hablar con mysql
            // crear la conexion con los datos de arriba
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    // que los errores salten como excepcion
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // los resultados vienen como array con nombre (fila, nombre)
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            ); // <-- aqui estaba el ; que faltaba
        } catch (PDOException $e) {
            // si falla la conexion muestra el error
            die("Error de conexion: " . $e->getMessage());
        }

        return $this->conn; // devolveria la conexion
    }
}