<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';     
require_once __DIR__ . '/BaseDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$correo     = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if (!$correo || !$contrasena) {
    $_SESSION['error_login'] = "Todos los campos son obligatorios";
    header("Location: index.php?login=error");
    exit;
}

$db = new BaseDatos();

$sql = "
    SELECT 
        u.id_usuario,
        u.nombre,
        u.contrasena_hash,
        r.nombre_rol
    FROM usuarios u
    JOIN usuario_rol ur ON u.id_usuario = ur.id_usuario
    JOIN roles r ON ur.id_rol = r.id_rol
    WHERE u.correo = :correo
    LIMIT 1
";

$stmt = $db->query($sql, ['correo' => $correo]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($contrasena, $usuario['contrasena_hash'])) {
    $_SESSION['error_login'] = "Correo o contraseña incorrectos";
    header("Location: index.php?login=error");
    exit;
}

$_SESSION['id_usuario'] = $usuario['id_usuario'];
$_SESSION['nombre']     = $usuario['nombre'];
$_SESSION['correo']     = $correo;
$_SESSION['rol']        = $usuario['nombre_rol'];

header("Location: index.php");
exit;