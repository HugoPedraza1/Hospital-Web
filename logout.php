<?php
require_once __DIR__ . '/config/config.php';

// este no es visual solo es logica para cerrar sesion, por eso no tiene html ni nada
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
redirect('login.php');