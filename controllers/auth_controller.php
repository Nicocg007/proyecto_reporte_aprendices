<?php
// se inicia la sesion
require_once '../model/seguridad_helper.php';
require_once '../config/database.php';
require_once '../model/auth_helper.php';
require_once '../vendor/autoload.php';
iniciarSesionSegura();

// Por post
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    verificarCsrf();
    // leer los datos enviados
    $documento = trim($_POST['documento'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // rate limit por ip + documento
    $clave_rate = 'login_' . ($_SERVER['REMOTE_ADDR'] ?? 'ip') . '_' . $documento;
    if (rateLimitExcedido($clave_rate, 5, 300)) {
        header('Location: ../views/login.php?error=7');
        exit();
    }

    // validar que no esten vacios
    if(empty($documento) || empty($password)){
        header('Location: ../views/login.php?error=4');
        exit();
    }

    // conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // buscar el usuario por ducumento
    $usuario = (new \App\Repository\UserRepository($conn))->findForLogin($documento);

    // si no existe el usuario
    // mensaje unificado: no distinguir usuario inexistente vs password mala
    if(!$usuario || !verificarPassword($password, $usuario['password'])){
        rateLimitRegistrarFallo($clave_rate, 5, 300);
        header('Location: ../views/login.php?error=1');
        exit();
    }

    // si el usuario esta inactivo
    if($usuario['estado'] !== 'Activo'){
        header('Location: ../views/login.php?error=6'); // usuario inactivo
        exit();
    }

    rateLimitLimpiar($clave_rate);

    // regenerar id de sesion para evitar fixation
    session_regenerate_id(true);

    // guardar datos en la sesion
    $_SESSION['id_usuario']     = $usuario['id_usuario'];
    $_SESSION['numero_documento'] = $documento;
    $_SESSION['nombre']         = $usuario['nombre'];
    $_SESSION['apellido']       = $usuario['apellido'];
    $_SESSION['correo']         = $usuario['correo'];
    $_SESSION['rol']            = $usuario['rol'];

    $destino = (new \App\Service\AuthService())->dashboardForRole($usuario['rol']);
    header('Location: ../views/' . $destino);
    exit();

}
?>