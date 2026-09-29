<?php
// controlador para registrar asistencia con el lector rfid
// recibe el uid de la tarjeta por post y guarda el ingreso

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

// la ficha del aprendiz para conocer la hora de entrada
$stmt = $conn->prepare("SELECT f.hora_entrada
    FROM usuario_has_ficha uf
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE uf.id_aprendiz = ?
    LIMIT 1");
$stmt->execute([$aprendiz['id_usuario']]);
$ficha = $stmt->fetch();

// revisar si el aprendiz ya marco entrada hoy
$stmt = $conn->prepare("SELECT id_ingreso FROM ingreso
    WHERE id_aprendiz = ? AND fecha = CURDATE()");
$stmt->execute([$aprendiz['id_usuario']]);
$ya_registrado = $stmt->fetch();

// si ya tiene registro hoy no se duplica
if ($ya_registrado) {
    header('Location: ../views/rfid.php?error=3&nombre=' . urlencode($aprendiz['nombre'] . ' ' . $aprendiz['apellido']));
    exit();
}

// hora actual del sistema
$ahora = date('H:i:s');

// comparar la hora actual con la hora de entrada de la ficha
$hora_entrada = $ficha['hora_entrada'] ?? null;
$estado_asistencia = 'Normal';
$minutos_retardo = 0;

if ($hora_entrada !== null && $ahora > $hora_entrada) {
    $estado_asistencia = 'Retardo';
    // diferencia en minutos entre la hora actual y la de entrada
    $minutos_retardo = (int)round((strtotime($ahora) - strtotime($hora_entrada)) / 60);
}

// guardar el ingreso en la base
$stmt = $conn->prepare("INSERT INTO ingreso
    (id_aprendiz, fecha, hora_entrada_registrada, minutos_retardo, estado_asistencia)
    VALUES (?, CURDATE(), ?, ?, ?)");
$stmt->execute([$aprendiz['id_usuario'], $ahora, $minutos_retardo, $estado_asistencia]);

// nombre completo del aprendiz para el mensaje
$nombre_completo = $aprendiz['nombre'] . ' ' . $aprendiz['apellido'];

// ir a la pantalla del lector con el resultado
header('Location: ../views/rfid.php?ok=1&nombre=' . urlencode($nombre_completo) . '&estado=' . $estado_asistencia . '&retardo=' . $minutos_retardo . '&hora=' . $ahora);
exit();