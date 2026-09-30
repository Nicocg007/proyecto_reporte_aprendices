<?php
// controlador del flujo de excusas
// maneja enviar una excusa, aprobarla o rechazarla

require_once '../model/auth_helper.php';
require_once '../config/database.php';
requiereLogin();

// rol del usuario que hace la accion
$rol_actual = getRol();
$id_usuario = $_SESSION['id_usuario'];

// conexion a la base de datos
$db = new Database();
$conn = $db->getConnection();

// accion que llega por post
$accion = $_POST['accion'] ?? '';

// enviar excusa solo lo hace el aprendiz
if ($accion === 'enviar') {

    if ($rol_actual !== 'Aprendiz') {
        header('Location: ../views/login.php');
        exit();
    }

    // datos del formulario
    $fecha = trim($_POST['fecha_inasistencia'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');

    // la fecha y el motivo son obligatorios
    if ($fecha === '' || $motivo === '') {
        header('Location: ../views/aprendiz_excusas.php?error_campos=1');
        exit();
    }

    // ruta del archivo adjunto, queda vacia si no se sube nada
    $archivo_guardado = '';

    // si llego un archivo se valida y se guarda
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {

        // extensiones permitidas
        $permitidas = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $permitidas)) {
            header('Location: ../views/aprendiz_excusas.php?error_archivo=1');
            exit();
        }

        // tamano maximo de 5 megas
        if ($_FILES['archivo']['size'] > 5 * 1024 * 1024) {
            header('Location: ../views/aprendiz_excusas.php?error_tamano=1');
            exit();
        }

        // carpeta donde se guardan los adjuntos
        $carpeta = __DIR__ . '/../uploads/excusas/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        // nombre unico para no sobrescribir archivos
        $nombre_archivo = 'excusa_' . $id_usuario . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $extension;

        if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $carpeta . $nombre_archivo)) {
            header('Location: ../views/aprendiz_excusas.php?error_subida=1');
            exit();
        }

        // se guarda la ruta relativa al proyecto
        $archivo_guardado = 'uploads/excusas/' . $nombre_archivo;
    }

    // se guarda la excusa en estado pendiente
    $stmt = $conn->prepare("INSERT INTO excusa
        (id_aprendiz, fecha_inasistencia, archivo_adjunto, observacion, estado)
        VALUES (?, ?, ?, ?, 'Pendiente')");
    $stmt->execute([$id_usuario, $fecha, $archivo_guardado, $motivo]);

    header('Location: ../views/aprendiz_excusas.php?enviada=1');
    exit();
}

// aprobar o rechazar lo hace el instructor o el admin
if ($accion === 'aprobar' || $accion === 'rechazar') {

    if ($rol_actual !== 'Administrador' && $rol_actual !== 'Instructor') {
        header('Location: ../views/login.php');
        exit();
    }

    $id_excusa = (int)($_POST['id_excusa'] ?? 0);

    // la pantalla a la que se vuelve segun el rol
    $redirigir = $rol_actual === 'Administrador' ? '../views/admin_excusas.php' : '../views/instructor_excusas.php';

    if ($id_excusa <= 0) {
        header('Location: ' . $redirigir . '?error=1');
        exit();
    }

    // el instructor solo revisa excusas de sus propias fichas
    if ($rol_actual === 'Instructor') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM excusa e
            INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
            INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
            WHERE e.id_excusa = ? AND f.id_instructor_encargado = ?");
        $stmt->execute([$id_excusa, $id_usuario]);
        if ($stmt->fetch()['total'] == 0) {
            header('Location: ' . $redirigir . '?error=1');
            exit();
        }
    }

    // el estado nuevo segun la accion
    $nuevo_estado = $accion === 'aprobar' ? 'Aprobada' : 'Rechazada';

    // solo se revisa si sigue pendiente
    $stmt = $conn->prepare("UPDATE excusa
        SET estado = ?, id_instructor_revisor = ?, fecha_revision = NOW()
        WHERE id_excusa = ? AND estado = 'Pendiente'");
    $stmt->execute([$nuevo_estado, $id_usuario, $id_excusa]);

    // si no se cambio nada la excusa ya habia sido revisada
    if ($stmt->rowCount() === 0) {
        header('Location: ' . $redirigir . '?error=1');
        exit();
    }

    // al aprobar se corrige el ingreso de ese dia si existe
    if ($accion === 'aprobar') {
        $stmt = $conn->prepare("SELECT id_aprendiz, fecha_inasistencia FROM excusa WHERE id_excusa = ?");
        $stmt->execute([$id_excusa]);
        $excusa = $stmt->fetch();

        if ($excusa) {
            $stmt = $conn->prepare("SELECT id_ingreso FROM ingreso WHERE id_aprendiz = ? AND fecha = ?");
            $stmt->execute([$excusa['id_aprendiz'], $excusa['fecha_inasistencia']]);
            $ingreso = $stmt->fetch();

            // si hay un ingreso ese dia se vuelve normal y sin retardo
            if ($ingreso) {
                $stmt = $conn->prepare("UPDATE ingreso
                    SET estado_asistencia = 'Normal', minutos_retardo = 0
                    WHERE id_ingreso = ?");
                $stmt->execute([$ingreso['id_ingreso']]);

                $stmt = $conn->prepare("UPDATE excusa SET id_ingreso = ? WHERE id_excusa = ?");
                $stmt->execute([$ingreso['id_ingreso'], $id_excusa]);
            }
        }
    }

    // mensaje segun lo que se hizo
    $resultado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';
    header('Location: ' . $redirigir . '?revisada=' . $resultado);
    exit();
}

// si no llego una accion valida se regresa al login
header('Location: ../views/login.php');
exit();