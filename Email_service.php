<?php

function enviarCodigoVerificacion(string $correo, string $nombre, string $codigo): bool {
    $apiKey = getenv('RESEND_API_KEY');

    $html = "
    <body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:40px 0;'>
        <table width='520' style='margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;'>
            <tr><td style='background:#000;padding:32px;text-align:center;'>
                <h1 style='color:#fff;margin:0;letter-spacing:4px;'>ELEMENT</h1>
            </td></tr>
            <tr><td style='padding:40px;'>
                <h2 style='color:#000;'>Hola, {$nombre} 👋</h2>
                <p style='color:#555;'>Ingresa el siguiente código para verificar tu cuenta:</p>
                <div style='background:#f8f8f8;border:2px dashed #000;border-radius:10px;padding:28px;text-align:center;margin:24px 0;'>
                    <p style='font-size:42px;font-weight:700;letter-spacing:10px;margin:0;color:#000;'>{$codigo}</p>
                </div>
                <p style='color:#888;font-size:13px;'>⏱ Expira en <strong>15 minutos</strong>.</p>
            </td></tr>
            <tr><td style='background:#f8f8f8;padding:20px;text-align:center;border-top:1px solid #eee;'>
                <p style='color:#aaa;font-size:12px;margin:0;'>© 2025 ELEMENT Tiendas</p>
            </td></tr>
        </table>
    </body>";

    $payload = json_encode([
        'from'    => 'ELEMENT Tiendas <onboarding@resend.dev>',
        'to'      => [$correo],
        'subject' => 'Código de verificación - ELEMENT',
        'html'    => $html
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log("Resend error ($httpCode): " . $response);
        return false;
    }

    return true;
}