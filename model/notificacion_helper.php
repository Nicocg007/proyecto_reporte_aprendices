<?php
// arma las notificaciones que se muestran en la campana segun el rol
require_once __DIR__ . '/../vendor/autoload.php';

function obtenerNotificaciones($conn, $id_usuario, $rol) {
    $counts = (new \App\Repository\ExcuseRepository($conn))
        ->findNotificationCounts((int) $id_usuario, $rol);
    return (new \App\Service\ExcuseService())->notifications($counts, $rol);
}
