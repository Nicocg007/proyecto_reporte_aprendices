<?php
// carga config/env.php; si no existe corta con mensaje generico
function cargarEnv() {
    static $env = null;
    if ($env !== null) {
        return $env;
    }
    $ruta = __DIR__ . '/env.php';
    if (!is_file($ruta)) {
        http_response_code(500);
        die('Error de configuracion del sistema.');
    }
    $datos = require $ruta;
    if (!is_array($datos)) {
        http_response_code(500);
        die('Error de configuracion del sistema.');
    }
    $env = $datos;
    return $env;
}
