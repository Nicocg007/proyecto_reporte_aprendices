<?php
// helpers de seguridad: sesion, csrf, escape y rate limit

// inicia la sesion con cookies endurecidas (httponly, samesite, secure si https)
function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $seguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $seguro,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// escapa texto para html
function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// token csrf de la sesion
function csrfToken() {
    iniciarSesionSegura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// campo hidden para formularios
function csrfField() {
    $token = e(csrfToken());
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// valida el token csrf del post; corta si falla
function verificarCsrf() {
    iniciarSesionSegura();
    $enviado = $_POST['csrf_token'] ?? '';
    $esperado = $_SESSION['csrf_token'] ?? '';
    if ($enviado === '' || $esperado === '' || !hash_equals($esperado, $enviado)) {
        http_response_code(403);
        die('Solicitud no valida.');
    }
}

// rate limit simple en sesion: clave, max intentos, segundos de bloqueo
function rateLimitExcedido($clave, $max = 5, $segundos = 300) {
    iniciarSesionSegura();
    $ahora = time();
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    $dato = $_SESSION['rate_limit'][$clave] ?? ['intentos' => 0, 'bloqueado_hasta' => 0];
    if ($dato['bloqueado_hasta'] > $ahora) {
        return true;
    }
    return false;
}

function rateLimitRegistrarFallo($clave, $max = 5, $segundos = 300) {
    iniciarSesionSegura();
    $ahora = time();
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    $dato = $_SESSION['rate_limit'][$clave] ?? ['intentos' => 0, 'bloqueado_hasta' => 0];
    if ($dato['bloqueado_hasta'] > $ahora) {
        return;
    }
    $dato['intentos']++;
    if ($dato['intentos'] >= $max) {
        $dato['bloqueado_hasta'] = $ahora + $segundos;
        $dato['intentos'] = 0;
    }
    $_SESSION['rate_limit'][$clave] = $dato;
}

function rateLimitLimpiar($clave) {
    iniciarSesionSegura();
    if (isset($_SESSION['rate_limit'][$clave])) {
        unset($_SESSION['rate_limit'][$clave]);
    }
}

// cierra sesion borrando cookie y regenerando
function cerrarSesionSegura() {
    iniciarSesionSegura();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
