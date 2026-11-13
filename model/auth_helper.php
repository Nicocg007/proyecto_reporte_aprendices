<?php
require_once __DIR__ . '/seguridad_helper.php';
require_once __DIR__ . '/../vendor/autoload.php';
// genera el hash seguro de una contraseña para guardarla en la base
function hashearPassword($password){
    return (new \App\Service\PasswordService())->hash($password);
}

// revisa si una contraseña coincide con el hash guardado
function verificarPassword($ingresada, $guardada){
    return (new \App\Service\PasswordService())->verify($ingresada, $guardada);
}

// funcion que verifica que el usuario este logueado
// se usa al inicio de las paginas internas
function requiereLogin(){
    // si la sesion no esta inciada la inicia
    if(session_status() === PHP_SESSION_NONE) {
        iniciarSesionSegura();
    }

    // si no existe id_usuario en la sesion = no esta logueado
    if(!isset($_SESSION['id_usuario'])) {
        // lo redirige al login para que se autentifique
        header('Location: login.php');
        exit(); // corto la ejecucion
    }
}

// funcion para verificar que el usuario tenga el rol correcto
function requiereRol($rol_requerido){
    requiereLogin(); // verifica que este logueado

    // si el rol de la sesion no coincide con lo requerido
    if(($_SESSION['rol'] ?? '') !== $rol_requerido){
        // si esta logueado con otro rol lo llevo a su propio panel
        // asi no parece que se le cerro la sesion
        $destino = (new \App\Service\AuthService())
            ->dashboardForRole($_SESSION['rol'] ?? '');
        header('Location: ' . $destino);
        exit();
    }
}


// permite varios roles (ej. reportes: admin e instructor y aprendiz con alcance propio)
function requiereRoles($roles_permitidos){
    requiereLogin();
    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, (array)$roles_permitidos, true)) {
        $destino = (new \App\Service\AuthService())->dashboardForRole($rol);
        header('Location: ' . $destino);
        exit();
    }
}

// funcion que devuelve al usuario como un array
function getUsuarioActual(){
    if(session_status() === PHP_SESSION_NONE){
        iniciarSesionSegura();
    }

    // si no hay sesio devuelve null
    return (new \App\Service\AuthService())->userSummary($_SESSION);
}

// funcion devuelve true si esta logueado, false si no
function isLoggedIn(){
    if(session_status() === PHP_SESSION_NONE){
        iniciarSesionSegura();
    }
    return (new \App\Service\AuthService())->isAuthenticated($_SESSION);
    
}

// funcion que devuelve el rol actual
function getRol(){
    if(session_status() === PHP_SESSION_NONE){
        iniciarSesionSegura();
    }
    return $_SESSION['rol'] ?? '';
}
?>