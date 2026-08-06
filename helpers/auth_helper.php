<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkAuth() {
    if (!isLoggedIn()) {
        header('Location: ../views/login.php?error=no_session');
        exit;
    }
}

function checkRole($allowed_roles = []) {
    checkAuth();
    if (!in_array($_SESSION['user_rol'], $allowed_roles)) {
        header('Location: ../views/login.php?error=unauthorized');
        exit;
    }
}