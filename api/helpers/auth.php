<?php
require_once __DIR__ . '/session.php';

if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: index.php?login=required");
    exit;
}
