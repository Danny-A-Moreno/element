<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$db = new BaseDatos();

$correo = $_SESSION['correo_verificacion'];

$codigo = $db->crearCodigoVerificacion($correo);

if (!enviarCodigoCorreo($correo, $codigo)) {
    die("Error enviando correo");
}

