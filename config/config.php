<?php
// constantes, funciones globales, session_start
session_start();
require_once __DIR__ . '/database.php';

define('APP_NAME', 'Hospital Web');
define('BASE_URL', 'http://localhost:8000');

function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function requireLogin() {
    if (!isLoggedIn()) redirect('login.php');
}

function requireRole($rol) {
    requireLogin();
    if ($_SESSION['rol'] !== $rol && $_SESSION['rol'] !== 'admin') {
        redirect('login.php');
    }
}
