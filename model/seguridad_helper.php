<?php
// helpers de seguridad: sesion, csrf, escape y rate limit
require_once __DIR__ . '/../vendor/autoload.php';

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
    return (new \App\Service\SecurityService())->escape($valor);
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
function verificarCsrf(?string $enviado = null) {
    iniciarSesionSegura();
    $enviado = $enviado ?? ($_POST['csrf_token'] ?? '');
    $esperado = $_SESSION['csrf_token'] ?? '';
    if (!(new \App\Service\SecurityService())->isValidCsrfToken($esperado, $enviado)) {
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
    $dato = $_SESSION['rate_limit'][$clave] ?? [];
    return (new \App\Service\SecurityService())->isRateLimited($dato, $ahora);
}

function rateLimitRegistrarFallo($clave, $max = 5, $segundos = 300) {
    iniciarSesionSegura();
    $ahora = time();
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    $dato = $_SESSION['rate_limit'][$clave] ?? [];
    $_SESSION['rate_limit'][$clave] = (new \App\Service\SecurityService())
        ->registerFailure($dato, $ahora, $max, $segundos);
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
