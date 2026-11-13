<?php

require_once __DIR__ . '/model/seguridad_helper.php';
iniciarSesionSegura();

// Si ya tiene sesion activa, redirigir al dashboard
$authService = new \App\Service\AuthService();
if ($authService->isAuthenticated($_SESSION)) {
    header('Location: views/' . $authService->dashboardForRole($_SESSION['rol'] ?? ''));
    exit();
}

// Sin sesion, ir al login
header('Location: views/login.php');
exit();
