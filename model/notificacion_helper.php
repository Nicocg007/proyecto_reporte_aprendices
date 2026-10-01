<?php
// arma las notificaciones que se muestran en la campana segun el rol
function obtenerNotificaciones($conn, $id_usuario, $rol) {
    $notificaciones = [];

    if ($rol === 'Administrador') {
        // excusas pendientes de revisar en todo el sistema
        $total = $conn->query("SELECT COUNT(*) AS total FROM excusa WHERE estado = 'Pendiente'")->fetch()['total'];
        if ($total > 0) {
            $notificaciones[] = [
                'texto' => "Tienes $total excusa(s) pendiente(s) por revisar",
                'url' => 'admin_excusas.php',
            ];
        }
    } elseif ($rol === 'Instructor') {
        // excusas pendientes solo de las fichas a su cargo
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM excusa e
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE e.estado = 'Pendiente' AND f.id_instructor_encargado = ?");
        $stmt->execute([$id_usuario]);
        $total = $stmt->fetch()['total'];
        if ($total > 0) {
            $notificaciones[] = [
                'texto' => "Tienes $total excusa(s) pendiente(s) por revisar",
                'url' => 'instructor_excusas.php',
            ];
        }
    } else {
        // el aprendiz ve el estado de sus propias excusas
        $stmt = $conn->prepare("SELECT estado, COUNT(*) AS total FROM excusa WHERE id_aprendiz = ? GROUP BY estado");
        $stmt->execute([$id_usuario]);
        $conteo = ['Pendiente' => 0, 'Aprobada' => 0, 'Rechazada' => 0];
        foreach ($stmt->fetchAll() as $fila) {
            $conteo[$fila['estado']] = $fila['total'];
        }
        if ($conteo['Pendiente'] > 0) {
            $notificaciones[] = [
                'texto' => "Tienes {$conteo['Pendiente']} excusa(s) en espera de revision",
                'url' => 'aprendiz_excusas.php',
            ];
        }
        if ($conteo['Aprobada'] > 0) {
            $notificaciones[] = [
                'texto' => "{$conteo['Aprobada']} excusa(s) aprobada(s)",
                'url' => 'aprendiz_excusas.php',
            ];
        }
        if ($conteo['Rechazada'] > 0) {
            $notificaciones[] = [
                'texto' => "{$conteo['Rechazada']} excusa(s) rechazada(s)",
                'url' => 'aprendiz_excusas.php',
            ];
        }
    }

    return $notificaciones;
}
