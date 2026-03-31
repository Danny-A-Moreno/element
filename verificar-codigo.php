<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/BaseDatos.php';
/* =============================
   VALIDACIONES INICIALES
============================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$codigo = trim($_POST['codigo'] ?? '');

if ($codigo === '') {
    $_SESSION['error_verificacion'] = 'Debes ingresar el código';
    header('Location: verificar.php');
    exit;
}

if (!isset($_SESSION['correo_verificacion'])) {
    $_SESSION['error_verificacion'] = 'Sesión inválida';
    header('Location: index.php');
    exit;
}

$correo = $_SESSION['correo_verificacion'];

$db = new BaseDatos();

/* =============================
   BUSCAR CÓDIGO
============================= */

$sql = "
    SELECT *
    FROM codigos_verificacion
    WHERE correo = ?
      AND codigo = ?
      AND usado = 0
      AND fecha_expiracion > NOW()
    LIMIT 1
";

$stmt = $db->query($sql, [$correo, $codigo]);
$registro = $stmt->fetch();

if (!$registro) {
    $_SESSION['error_verificacion'] = 'Código inválido o expirado';
    header('Location: verificar.php');
    exit;
}

/* =============================
   MARCAR CÓDIGO COMO USADO
============================= */

$db->query(
    "UPDATE codigos_verificacion SET usado = 1 WHERE id = ?",
    [$registro['id']]
);

/* =============================
   BUSCAR USUARIO
============================= */

$usuario = $db->buscarUsuarioPorCorreo($correo);

if (!$usuario) {
    $_SESSION['error_verificacion'] = 'Usuario no encontrado';
    header('Location: index.php');
    exit;
}

/* =============================
   INICIAR SESIÓN
============================= */

$_SESSION['id_usuario'] = $usuario['id_usuario'];
$_SESSION['nombre']     = $usuario['nombre'];
$_SESSION['correo']     = $correo;

/* Limpieza */
unset($_SESSION['correo_verificacion']);

/* =============================
   REDIRECCIÓN FINAL
============================= */

$redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
unset($_SESSION['redirect_after_login']);

header("Location: $redirect");
exit;
