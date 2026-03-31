<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function enviarCodigoVerificacion(string $correo, string $nombre, string $codigo): bool {
    $mail = new PHPMailer(true);

    try {
        // Servidor SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($correo, $nombre);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Código de verificación - ELEMENT';
        $mail->Body    = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0; padding:0; background:#f4f4f4; font-family: Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='520' cellpadding='0' cellspacing='0' style='background:#ffffff; border-radius:12px; overflow:hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);'>
                            
                            <!-- HEADER -->
                            <tr>
                                <td style='background:#000000; padding: 32px; text-align:center;'>
                                    <h1 style='color:#ffffff; margin:0; font-size:28px; font-weight:700; letter-spacing:4px;'>ELEMENT</h1>
                                </td>
                            </tr>

                            <!-- BODY -->
                            <tr>
                                <td style='padding: 40px 40px 20px;'>
                                    <h2 style='color:#000; font-size:20px; margin:0 0 12px;'>Hola, {$nombre} 👋</h2>
                                    <p style='color:#555; font-size:15px; line-height:1.6; margin:0 0 28px;'>
                                        Gracias por registrarte en <strong>ELEMENT</strong>. Para verificar tu cuenta, ingresa el siguiente código:
                                    </p>

                                    <!-- CÓDIGO -->
                                    <div style='background:#f8f8f8; border:2px dashed #000; border-radius:10px; padding:28px; text-align:center; margin-bottom:28px;'>
                                        <p style='margin:0 0 8px; font-size:13px; color:#888; text-transform:uppercase; letter-spacing:1px;'>Tu código de verificación</p>
                                        <p style='margin:0; font-size:42px; font-weight:700; color:#000; letter-spacing:10px;'>{$codigo}</p>
                                    </div>

                                    <p style='color:#888; font-size:13px; line-height:1.6; margin:0 0 8px;'>
                                        ⏱ Este código expira en <strong>15 minutos</strong>.
                                    </p>
                                    <p style='color:#888; font-size:13px; line-height:1.6; margin:0;'>
                                        Si no creaste esta cuenta, ignora este correo.
                                    </p>
                                </td>
                            </tr>

                            <!-- FOOTER -->
                            <tr>
                                <td style='background:#f8f8f8; padding:20px 40px; text-align:center; border-top:1px solid #eee;'>
                                    <p style='color:#aaa; font-size:12px; margin:0;'>© 2025 ELEMENT Tiendas · Todos los derechos reservados</p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $mail->AltBody = "Tu código de verificación ELEMENT es: {$codigo}. Expira en 15 minutos.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error enviando correo: " . $mail->ErrorInfo);
        return false;
    }
}