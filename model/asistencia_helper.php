<?php
// marca la inasistencia de los aprendices que no registraron entrada ese dia
require_once __DIR__ . '/../vendor/autoload.php';

function marcarInasistencias($conn, $fecha) {
    return (new \App\Repository\AttendanceRepository($conn))
        ->markAbsences($fecha, date('Y-m-d'), date('H:i:s'));
}
