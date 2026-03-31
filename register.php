<?php
session_start();
require_once __DIR__ . '/BaseDatos.php';
require_once __DIR__ . '/Email_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$nombre   = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$correo   = trim($_POST['correo']);
$password = $_POST['password'];
$terminos = $_POST['terminos'] ?? '';

// Validar campos
if (!$nombre || !$apellido || !$correo || !$password) {
    $_SESSION['error_registro'] = "Todos los campos son obligatorios";
    header("Location: registro.php");
    exit;
}

// Validar términos
if (!$terminos) {
    $_SESSION['error_registro'] = "Debes aceptar los términos y condiciones";
    header("Location: registro.php");
    exit;
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_registro'] = "El correo no tiene un formato válido";
    header("Location: registro.php");
    exit;
}

$db = new BaseDatos();

// Verificar correo único
if ($db->buscarUsuarioPorCorreo($correo)) {
    $_SESSION['error_registro'] = "Este correo ya está registrado";
    header("Location: registro.php");
    exit;
}

// Registrar usuario
$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$id_usuario   = $db->registrarUsuario($nombre, $apellido, $correo, $passwordHash);

// Generar código de 6 dígitos
$codigo  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expira  = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Guardar código en la BD
$db->query(
    "INSERT INTO codigos_verificacion (correo, codigo, fecha_expiracion, usado) 
     VALUES (:correo, :codigo, :expira, 0)
     ON DUPLICATE KEY UPDATE codigo = :codigo2, fecha_expiracion = :expira2, usado = 0",
    [
        'correo'  => $correo,
        'codigo'  => $codigo,
        'expira'  => $expira,
        'codigo2' => $codigo,
        'expira2' => $expira,
    ]
);

// Enviar correo
$enviado = enviarCodigoVerificacion($correo, $nombre, $codigo);

if (!$enviado) {
    $_SESSION['error_registro'] = "Error al enviar el correo de verificación. Intenta de nuevo.";
    header("Location: registro.php");
    exit;
}

// Guardar correo en sesión para verificar
$_SESSION['correo_verificacion'] = $correo;
$_SESSION['id_usuario']          = $id_usuario;
$_SESSION['nombre']              = $nombre;
$_SESSION['correo']              = $correo;

header("Location: verificar.php");
exit;