<?php
// marca la inasistencia de los aprendices que no registraron entrada ese dia

function marcarInasistencias($conn, $fecha) {
    // aprendices activos de alguna ficha sin ingreso ese dia
    $sql = "SELECT a.id_usuario, f.hora_salida
        FROM usuario_has_ficha uf
        INNER JOIN usuario a ON a.id_usuario = uf.id_aprendiz AND a.id_rol = 3 AND a.estado = 'Activo'
        INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
        WHERE NOT EXISTS (
            SELECT 1 FROM ingreso i WHERE i.id_aprendiz = a.id_usuario AND i.fecha = ?
        )";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fecha]);
    $candidatos = $stmt->fetchAll();

    $hoy = date('Y-m-d');
    $ahora = date('H:i:s');
    $marcados = 0;

    foreach ($candidatos as $c) {
        // si es hoy solo se marca despues de la hora de salida de la ficha
        if ($fecha === $hoy && $ahora <= $c['hora_salida']) {
            continue;
        }

        // revisar de nuevo que no exista para no duplicar
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM ingreso WHERE id_aprendiz = ? AND fecha = ?");
        $stmt->execute([$c['id_usuario'], $fecha]);
        if ($stmt->fetch()['total'] > 0) {
            continue;
        }

        $stmt = $conn->prepare("INSERT INTO ingreso
            (id_aprendiz, fecha, hora_entrada_registrada, hora_salida_registrada, minutos_retardo, salida_temprana, estado_asistencia)
            VALUES (?, ?, NULL, NULL, 0, 0, 'Inasistencia')");
        $stmt->execute([$c['id_usuario'], $fecha]);
        $marcados++;
    }

    return $marcados;
}
