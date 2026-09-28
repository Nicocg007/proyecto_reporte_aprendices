<?php
// funcion que verifica que el usuario este logueado
// se usa al inicio de las paginas internas
function requiereLogin(){
    // si la sesion no esta inciada la inicia
    if(session_status() === PHP_SESSION_NONE) {
        session_start();
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
    if($_SESSION['rol'] !== $rol_requerido){
        // lo saco al login
        header('Location: login.php');
        exit();
    }
}

// funcion que devuelve al usuario como un array
function getUsuarioActual(){
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    // si no hay sesio devuelve null
    if(!isset($_SESSION['id_usuario'])) {
        return null;
    }

    // devuelve los datos del usuario guardados en la sesion
    return [
        'id'       => $_SESSION['id_usuario'],
        'documento'=> $_SESSION['numero_documento'] ?? '',
        'nombre'   => $_SESSION['nombre'] ?? '',
        'apellido' => $_SESSION['apellido'] ?? '',
        'correo'   => $_SESSION['correo'] ?? '',
        'rol'      => $_SESSION['rol'] ?? '',
        'id_rol'   => $_SESSION['id_rol'] ?? 0
    ];
}

// funcion devuelve true si esta logueado, false si no
function isLoggedIn(){
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
    return isset($_SESSION['id_usuario']);
    
}

// funcion que devuelve el rol actual
function getRol(){
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
    return $_SESSION['rol'] ?? '';
}
?>