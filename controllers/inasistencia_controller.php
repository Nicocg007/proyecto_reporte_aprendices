<?php
// marca las inasistencias del dia. se puede correr por consola o desde el navegador como admin
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/asistencia_helper.php';

$es_cli = (php_sapi_name() === 'cli');

if (!$es_cli) {
    require_once __DIR__ . '/../model/auth_helper.php';
    requiereLogin();
    requiereRol('Administrador');
}

$db = new Database();
$conn = $db->getConnection();

// fecha a procesar: argumento en consola o parametro en la web, por defecto hoy
$fecha = $es_cli ? ($argv[1] ?? date('Y-m-d')) : ($_GET['fecha'] ?? date('Y-m-d'));

$marcados = marcarInasistencias($conn, $fecha);

if ($es_cli) {
    echo "Inasistencias marcadas para $fecha: $marcados\n";
} else {
    header('Location: ../views/admin_dashboard.php?inasistencias=' . $marcados);
    exit();
}
