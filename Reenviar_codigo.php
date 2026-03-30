<?php
session_start();
require_once __DIR__ . '/BaseDatos.php';
require_once __DIR__ . '/email_service.php';

if (!isset($_SESSION['correo_verificacion'])) {
    header('Location: registro.php');
    exit;
}

$correo = $_SESSION['correo_verificacion'];
$nombre = $_SESSION['nombre'] ?? 'Usuario';

$db = new BaseDatos();

// Generar nuevo código
$codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Actualizar código en BD
$db->query(
    "UPDATE codigos_verificacion 
     SET codigo = :codigo, fecha_expiracion = :expira, usado = 0 
     WHERE correo = :correo",
    ['codigo' => $codigo, 'expira' => $expira, 'correo' => $correo]
);

// Si no existe, insertar
$db->query(
    "INSERT IGNORE INTO codigos_verificacion (correo, codigo, fecha_expiracion, usado)
     VALUES (:correo, :codigo, :expira, 0)",
    ['correo' => $correo, 'codigo' => $codigo, 'expira' => $expira]
);

$enviado = enviarCodigoVerificacion($correo, $nombre, $codigo);

if ($enviado) {
    $_SESSION['success_verificacion'] = "Código reenviado correctamente.";
} else {
    $_SESSION['error_verificacion'] = "Error al reenviar el correo. Intenta de nuevo.";
}

header('Location: verificar.php');
exit;