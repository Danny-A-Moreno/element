<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function enviarCodigoCorreo($correo, $codigo)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'TU_CORREO@gmail.com';
        $mail->Password   = 'TU_CONTRASEÑA_DE_APLICACION';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('TU_CORREO@gmail.com', 'ELEMENT');
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Código de verificación';
        $mail->Body    = "<p>Tu código de verificación es:</p><h2>$codigo</h2>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
