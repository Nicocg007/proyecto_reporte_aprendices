<?php

session_start();

// Si ya tiene sesion activa, redirigir al dashboard
if (isset($_SESSION['id_usuario'])) {
    $rol = $_SESSION['rol'] ?? '';
    
    switch ($rol) {
        case 'Administrador':
            header('Location: views/admin_dashboard.php');
            break;
        case 'Instructor':
            header('Location: views/instructor_dashboard.php');
            break;
        case 'Aprendiz':
            header('Location: views/aprendiz_dashboard.php');
            break;
        default:
            header('Location: views/login.php');
            break;
    }
    exit();
}

// Sin sesion, ir al login
header('Location: views/login.php');
exit();
