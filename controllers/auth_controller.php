<?php
// se inicia la sesion
session_start();
require_once '../config/database.php';

// Por post
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // leer los datos enviados
    $documento = trim($_POST['documento'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // validar que no esten vacios
    if(empty($documento) || empty($password)){
        header('Location: ../views/login.php?error=4');
        exit();
    }

    // conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // buscar el usuario por ducumento
    $stmt = $conn->prepare(
        "SELECT u.id_usuario,u.nombre,u.apellido,u.correo,u.password,u.estado,r.nombre AS rol
        FROM usuario u INNER JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.numero_documento = :documento"

    );
    $stmt->execute([':documento' => $documento]);
    $usuario = $stmt->fetch();

    // si no existe el usuario
    if(!$usuario){
        header('Location: ../views/login.php?error=1'); // usuario no encontrado
        exit();
    }
    
    // si la contraseña no coincide
    if($usuario['password'] !==$password){
        header('Location: ../views/login.php?error=2'); // si la contraseña es incorrecta
        exit();
    }

    // si el usuario esta inactivo
    if($usuario['estado'] !== 'Activo'){
        header('Location: ../views/login.php?error=6'); // usuario inactivo
        exit();
    }

    // guardar datos en la sesion
    $_SESSION['id_usuario']     = $usuario['id_usuario'];
    $_SESSION['numero_documento'] = $documento;
    $_SESSION['nombre']         = $usuario['nombre'];
    $_SESSION['apellido']       = $usuario['apellido'];
    $_SESSION['correo']         = $usuario['correo'];
    $_SESSION['rol']            = $usuario['rol'];

    // redirigir segun el rol
    switch ($usuario['rol']) {
        case 'Administrador':
            header('Location: ../views/admin_dashboard.php');
            break;
        case 'Instructor':
            header('Location: ../views/instructor_dashboard.php');
            break;
        case 'Aprendiz':
            header('Location: ../views/aprendiz_dashboard.php');
            break;
        default:
            header('Location: ../views/login.php');
            break;
    }
    exit();

}
?>