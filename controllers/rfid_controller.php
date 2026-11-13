<?php
// controlador para registrar asistencia con el lector rfid
// recibe el uid de la tarjeta por post y marca la entrada o la salida

require_once '../model/auth_helper.php';
require_once '../model/seguridad_helper.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

// solo con sesion activa
requiereLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
}


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
$attendanceRepository = new \App\Repository\AttendanceRepository($conn);
$attendanceService = new \App\Service\AttendanceService();
$aprendiz = $attendanceRepository->findByRfid($uid);

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
$ficha = $attendanceRepository->findScheduleForLearner((int) $aprendiz['id_usuario']);

// registro de asistencia de hoy del aprendiz
$registro = $attendanceRepository->findForToday((int) $aprendiz['id_usuario']);

// nombre completo del aprendiz para el mensaje
$nombre_completo = $aprendiz['nombre'] . ' ' . $aprendiz['apellido'];

// hora actual del sistema
$ahora = date('H:i:s');

// caso 1: marcar la entrada
// no hay registro hoy, o existe pero sin entrada (por ejemplo una inasistencia automatica)
if (!$registro || $registro['hora_entrada_registrada'] === null) {
    // comparar la hora actual con la hora de entrada de la ficha
    $hora_entrada = $ficha['hora_entrada'] ?? null;
    $resultadoEntrada = $attendanceService->calculateCheckIn($ahora, $hora_entrada);
    $estado_asistencia = $resultadoEntrada['estado'];
    $minutos_retardo = $resultadoEntrada['minutos_retardo'];
    $attendanceRepository->saveCheckIn(
        (int) $aprendiz['id_usuario'],
        null,
        $ahora,
        $minutos_retardo,
        $estado_asistencia,
        $registro ? (int) $registro['id_ingreso'] : null
    );

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

    $attendanceRepository->saveCheckOut(
        (int) $registro['id_ingreso'],
        $ahora,
        $salida_temprana,
        $estado_asistencia
    );

    header('Location: ../views/rfid.php?ok=2&nombre=' . urlencode($nombre_completo) . '&hora=' . $ahora . '&temprana=' . $salida_temprana);
    exit();
}

// caso 3: ya tiene entrada y salida hoy
header('Location: ../views/rfid.php?error=4&nombre=' . urlencode($nombre_completo));
exit();
