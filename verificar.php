<?php
session_start();

// Si no hay correo en sesión, redirigir
if (!isset($_SESSION['correo_verificacion'])) {
    header('Location: registro.php');
    exit;
}

$correo = $_SESSION['correo_verificacion'];
// Mostrar solo parte del correo por privacidad: 
$partes      = explode('@', $correo);
$nombreOculto = substr($partes[0], 0, 3) . str_repeat('*', max(0, strlen($partes[0]) - 3));
$correoOculto = $nombreOculto . '@' . $partes[1];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar cuenta - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="registro.css">
    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0a0a;
            padding: 2rem;
        }

        .verify-card {
            background: #111;
            border: 1px solid #222;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .verify-logo {
            height: 60px;
            margin-bottom: 1.5rem;
        }

        .verify-icon {
            width: 64px;
            height: 64px;
            background: #1a1a1a;
            border: 2px solid #333;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem;
        }

        .verify-card h2 {
            color: #fff;
            font-size: 1.5rem;
            margin: 0 0 0.75rem;
        }

        .verify-card p {
            color: #888;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0 0 2rem;
        }

        .verify-card p strong {
            color: #ccc;
        }

        /* Inputs de código */
        .codigo-inputs {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .codigo-input {
            width: 48px;
            height: 56px;
            background: #1a1a1a;
            border: 2px solid #333;
            border-radius: 10px;
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            outline: none;
            transition: border-color 0.2s;
            caret-color: transparent;
        }

        .codigo-input:focus {
            border-color: #fff;
        }

        .codigo-input.filled {
            border-color: #555;
            background: #222;
        }

        /* Input hidden que se envía */
        #codigoCompleto {
            display: none;
        }

        .btn-verificar {
            width: 100%;
            padding: 1rem;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .btn-verificar:hover {
            background: #e0e0e0;
        }

        .btn-verificar:disabled {
            background: #333;
            color: #666;
            cursor: not-allowed;
        }

        .reenviar {
            font-size: 0.85rem;
            color: #666;
        }

        .reenviar a {
            color: #aaa;
            text-decoration: underline;
            cursor: pointer;
        }

        .reenviar a:hover {
            color: #fff;
        }

        /* Contador */
        .contador {
            font-size: 0.8rem;
            color: #555;
            margin-top: 0.5rem;
        }

        /* Alertas */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            text-align: left;
        }

        .alert-error {
            background: #2a1a1a;
            border: 1px solid #f44336;
            color: #f44336;
        }

        .alert-success {
            background: #1a2a1a;
            border: 1px solid #4caf50;
            color: #4caf50;
        }
    </style>
</head>
<body>
<div class="verify-container">
    <div class="verify-card">

        <img src="imagenes/logos/Element.jpg" class="verify-logo" alt="ELEMENT">

        <div class="verify-icon">✉️</div>

        <h2>Verifica tu cuenta</h2>
        <p>
            Enviamos un código de 6 dígitos a<br>
            <strong><?php echo htmlspecialchars($correoOculto); ?></strong>
        </p>

        <?php if (isset($_SESSION['error_verificacion'])): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($_SESSION['error_verificacion']); unset($_SESSION['error_verificacion']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_verificacion'])): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($_SESSION['success_verificacion']); unset($_SESSION['success_verificacion']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="verificar-codigo.php" id="formVerificar">
            <input type="hidden" name="correo" value="<?php echo htmlspecialchars($correo); ?>">
            <input type="hidden" name="codigo" id="codigoCompleto">

            <div class="codigo-inputs">
                <input type="text" class="codigo-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="codigo-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="codigo-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="codigo-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="codigo-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" class="codigo-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
            </div>

            <button type="submit" class="btn-verificar" id="btnVerificar" disabled>
                Verificar cuenta
            </button>
        </form>

        <p class="reenviar">
            ¿No recibiste el correo? 
            <a href="reenviar-codigo.php" id="btnReenviar">Reenviar código</a>
        </p>
        <p class="contador" id="contadorTexto">Puedes reenviar en <span id="contador">60</span>s</p>

    </div>
</div>

<script>
const inputs     = document.querySelectorAll('.codigo-input');
const btnVerificar = document.getElementById('btnVerificar');
const codigoHidden = document.getElementById('codigoCompleto');
const form       = document.getElementById('formVerificar');

// Navegar entre inputs automáticamente
inputs.forEach((input, i) => {
    input.addEventListener('input', (e) => {
        // Solo números
        input.value = input.value.replace(/[^0-9]/g, '');
        
        if (input.value) {
            input.classList.add('filled');
            if (i < inputs.length - 1) inputs[i + 1].focus();
        } else {
            input.classList.remove('filled');
        }

        actualizarCodigo();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && i > 0) {
            inputs[i - 1].focus();
            inputs[i - 1].value = '';
            inputs[i - 1].classList.remove('filled');
            actualizarCodigo();
        }
    });

    // Permitir pegar el código completo
    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        paste.split('').forEach((char, j) => {
            if (inputs[j]) {
                inputs[j].value = char;
                inputs[j].classList.add('filled');
            }
        });
        actualizarCodigo();
        if (inputs[paste.length]) inputs[paste.length].focus();
    });
});

function actualizarCodigo() {
    const codigo = Array.from(inputs).map(i => i.value).join('');
    codigoHidden.value = codigo;
    btnVerificar.disabled = codigo.length < 6;
}

// Contador de reenvío
let segundos = 60;
const btnReenviar    = document.getElementById('btnReenviar');
const contadorTexto  = document.getElementById('contadorTexto');
const contadorSpan   = document.getElementById('contador');

btnReenviar.style.pointerEvents = 'none';
btnReenviar.style.opacity = '0.4';

const intervalo = setInterval(() => {
    segundos--;
    contadorSpan.textContent = segundos;

    if (segundos <= 0) {
        clearInterval(intervalo);
        btnReenviar.style.pointerEvents = 'auto';
        btnReenviar.style.opacity = '1';
        contadorTexto.style.display = 'none';
    }
}, 1000);

// Focus en el primer input al cargar
inputs[0].focus();
</script>
</body>
</html>