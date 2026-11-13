<?php
// controlador del flujo de excusas
// maneja enviar una excusa, aprobarla o rechazarla

require_once '../model/auth_helper.php';
require_once '../model/seguridad_helper.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';
requiereLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
}


// rol del usuario que hace la accion
$rol_actual = getRol();
$id_usuario = $_SESSION['id_usuario'];

// conexion a la base de datos
$db = new Database();
$conn = $db->getConnection();
$excuseRepository = new \App\Repository\ExcuseRepository($conn);
$excuseService = new \App\Service\ExcuseService();

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
    if ($excuseService->validateSubmission($fecha, $motivo) !== null) {
        header('Location: ../views/aprendiz_excusas.php?error_campos=1');
        exit();
    }

    // ruta del archivo adjunto, queda vacia si no se sube nada
    $archivo_guardado = '';

    // si llego un archivo se valida y se guarda
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {

        $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['archivo']['tmp_name']);
        $uploadError = $excuseService->validateAttachment(
            $extension,
            (int) $_FILES['archivo']['size'],
            (string) $mime
        );
        if ($uploadError === 'tamano') {
            header('Location: ../views/aprendiz_excusas.php?error_tamano=1');
            exit();
        }
        if ($uploadError !== null) {
            header('Location: ../views/aprendiz_excusas.php?error_archivo=1');
            exit();
        }

        // carpeta donde se guardan los adjuntos
        $carpeta = __DIR__ . '/../uploads/excusas/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
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
    $excuseRepository->create((int) $id_usuario, $fecha, $archivo_guardado, $motivo);

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
        if (!$excuseRepository->canInstructorReview($id_excusa, (int) $id_usuario)) {
            header('Location: ' . $redirigir . '?error=1');
            exit();
        }
    }

    // el estado nuevo segun la accion
    $nuevo_estado = $accion === 'aprobar' ? 'Aprobada' : 'Rechazada';

    // solo se revisa si sigue pendiente
    if (!$excuseRepository->review($id_excusa, (int) $id_usuario, $nuevo_estado)) {
        header('Location: ' . $redirigir . '?error=1');
        exit();
    }

    // mensaje segun lo que se hizo
    $resultado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';
    header('Location: ' . $redirigir . '?revisada=' . $resultado);
    exit();
}

// si no llego una accion valida se regresa al login
header('Location: ../views/login.php');
exit();