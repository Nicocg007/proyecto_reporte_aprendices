<?php
// controlador para registrar asistencia con el lector rfid
// recibe el uid de la tarjeta por post y marca la entrada o la salida

require_once '../model/auth_helper.php';
require_once '../config/database.php';

// solo con sesion activa
requiereLogin();

// solo admin o instructor pueden registrar asistencia
$rol_actual = getRol();
if ($rol_actual !== 'Administrador' && $rol_actual !== 'Instructor') {
    header('Location: ../views/login.php');
    exit();
}

// uid de la tarjeta recibido por post
$uid = trim($_POST['uid'] ?? '');

// si no llego uid se regresa a la pantalla del lector
if ($uid === '') {
    header('Location: ../views/rfid.php');
    exit();
}

// conexion a la base de datos
$db = new Database();
$conn = $db->getConnection();

// buscar al aprendiz por el uid de la tarjeta
$stmt = $conn->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.estado
    FROM usuario u
    WHERE u.rfid_uid = ?");
$stmt->execute([$uid]);
$aprendiz = $stmt->fetch();

// la tarjeta no esta registrada en el sistema
if (!$aprendiz) {
    header('Location: ../views/rfid.php?error=1');
    exit();
}

// el usuario no esta activo
if ($aprendiz['estado'] !== 'Activo') {
    header('Location: ../views/rfid.php?error=2&nombre=' . urlencode($aprendiz['nombre'] . ' ' . $aprendiz['apellido']));
    exit();
}

// la ficha del aprendiz para conocer la hora de entrada y de salida
$stmt = $conn->prepare("SELECT f.hora_entrada, f.hora_salida
    FROM usuario_has_ficha uf
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE uf.id_aprendiz = ?
    LIMIT 1");
$stmt->execute([$aprendiz['id_usuario']]);
$ficha = $stmt->fetch();

// registro de asistencia de hoy del aprendiz
$stmt = $conn->prepare("SELECT id_ingreso, hora_entrada_registrada, hora_salida_registrada, estado_asistencia
    FROM ingreso
    WHERE id_aprendiz = ? AND fecha = CURDATE()");
$stmt->execute([$aprendiz['id_usuario']]);
$registro = $stmt->fetch();

// nombre completo del aprendiz para el mensaje
$nombre_completo = $aprendiz['nombre'] . ' ' . $aprendiz['apellido'];

// hora actual del sistema
$ahora = date('H:i:s');

// caso 1: marcar la entrada
// no hay registro hoy, o existe pero sin entrada (por ejemplo una inasistencia automatica)
if (!$registro || $registro['hora_entrada_registrada'] === null) {
    // comparar la hora actual con la hora de entrada de la ficha
    $hora_entrada = $ficha['hora_entrada'] ?? null;
    $estado_asistencia = 'Normal';
    $minutos_retardo = 0;

    if ($hora_entrada !== null && $ahora > $hora_entrada) {
        $estado_asistencia = 'Retardo';
        $minutos_retardo = (int)round((strtotime($ahora) - strtotime($hora_entrada)) / 60);
    }

    if ($registro) {
        // se completa el registro que ya existia sin entrada
        $stmt = $conn->prepare("UPDATE ingreso
            SET hora_entrada_registrada = ?, minutos_retardo = ?, estado_asistencia = ?
            WHERE id_ingreso = ?");
        $stmt->execute([$ahora, $minutos_retardo, $estado_asistencia, $registro['id_ingreso']]);
    } else {
        // guardar el ingreso nuevo en la base
        $stmt = $conn->prepare("INSERT INTO ingreso
            (id_aprendiz, fecha, hora_entrada_registrada, minutos_retardo, estado_asistencia)
            VALUES (?, CURDATE(), ?, ?, ?)");
        $stmt->execute([$aprendiz['id_usuario'], $ahora, $minutos_retardo, $estado_asistencia]);
    }

    header('Location: ../views/rfid.php?ok=1&nombre=' . urlencode($nombre_completo) . '&estado=' . $estado_asistencia . '&retardo=' . $minutos_retardo . '&hora=' . $ahora);
    exit();
}

// caso 2: marcar la salida
// hay entrada pero todavia no hay salida
if ($registro['hora_salida_registrada'] === null) {
    // comparar la hora actual con la hora de salida de la ficha
    $hora_salida = $ficha['hora_salida'] ?? null;
    $estado_asistencia = $registro['estado_asistencia'] ?? 'Normal';
    $salida_temprana = 0;

    if ($hora_salida !== null && $ahora < $hora_salida) {
        $estado_asistencia = 'Salida Temprana';
        $salida_temprana = 1;
    }

    $stmt = $conn->prepare("UPDATE ingreso
        SET hora_salida_registrada = ?, salida_temprana = ?, estado_asistencia = ?
        WHERE id_ingreso = ?");
    $stmt->execute([$ahora, $salida_temprana, $estado_asistencia, $registro['id_ingreso']]);

    header('Location: ../views/rfid.php?ok=2&nombre=' . urlencode($nombre_completo) . '&hora=' . $ahora . '&temprana=' . $salida_temprana);
    exit();
}

// caso 3: ya tiene entrada y salida hoy
header('Location: ../views/rfid.php?error=4&nombre=' . urlencode($nombre_completo));
exit();
