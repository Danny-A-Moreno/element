<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="registro.css">
</head>
<body>

<section class="auth-container">
    <div class="auth-card">

        <img src="imagenes/logos/Element.jpg" class="auth-logo" alt="ELEMENT">

        <h2>Crear cuenta</h2>
        <p class="auth-sub">Regístrate para comprar y guardar tus favoritos</p>

        <?php if (isset($_SESSION['error_registro'])): ?>
            <div class="alert-error">
                ✗ <?php echo htmlspecialchars($_SESSION['error_registro']); unset($_SESSION['error_registro']); ?>
            </div>
        <?php endif; ?>

        <form class="register-form" action="register.php" method="POST">

            <div class="form-row">
                <div class="input-group">
                    <label>Nombre</label>
                    <input name="nombre" type="text" placeholder="Tu nombre" required>
                </div>
                <div class="input-group">
                    <label>Apellido</label>
                    <input name="apellido" type="text" placeholder="Tu apellido" required>
                </div>
            </div>

            <div class="input-group">
                <label>Correo electrónico</label>
                <input name="correo" type="email" placeholder="correo@ejemplo.com" required>
            </div>

            <div class="input-group">
                <label>Contraseña</label>
                <div class="password-wrapper">
                    <input name="password" type="password" id="passwordInput" 
                        placeholder="Mínimo 6 caracteres" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('passwordInput', 'eye1')">
                        <svg id="eye1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Indicador de fuerza de contraseña -->
            <div class="password-strength" id="passwordStrength">
                <div class="strength-bars">
                    <span class="bar" id="bar1"></span>
                    <span class="bar" id="bar2"></span>
                    <span class="bar" id="bar3"></span>
                    <span class="bar" id="bar4"></span>
                </div>
                <span class="strength-label" id="strengthLabel">Ingresa una contraseña</span>
            </div>
            
            <div class="input-group">
                <label>Confirmar contraseña</label>
                <div class="password-wrapper">
                    <input name="password_confirm" type="password" id="passwordConfirm" 
                        placeholder="Repite tu contraseña" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('passwordConfirm', 'eye2')">
                        <svg id="eye2" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <span class="match-msg" id="matchMsg"></span>
            </div>

            <!-- Términos y condiciones -->
            <div class="terminos-wrapper">
                <input type="checkbox" name="terminos" id="terminos" required>
                <label for="terminos">
                    Acepto los <a href="terminos.php" target="_blank">términos y condiciones</a> y la 
                    <a href="privacidad.php" target="_blank">política de privacidad</a>
                </label>
            </div>

            <button type="submit" class="btn-submit">Crear cuenta</button>

        </form>

        <div class="auth-divider"><span>¿Ya tienes cuenta?</span></div>

        <p class="auth-footer">
            <a href="index.php?login=true">Inicia sesión aquí</a>
        </p>

    </div>
</section>

<script>
// Mostrar/ocultar contraseña
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Indicador de fuerza
document.getElementById('passwordInput').addEventListener('input', function() {
    const val = this.value;
    const bars  = ['bar1','bar2','bar3','bar4'].map(id => document.getElementById(id));
    const label = document.getElementById('strengthLabel');

    bars.forEach(b => { b.className = 'bar'; });

    if (!val.length) { label.textContent = 'Ingresa una contraseña'; return; }

    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const colores = ['','weak','fair','good','strong'];
    const textos  = ['','Débil','Regular','Buena','Fuerte'];

    for (let i = 0; i < score; i++) bars[i].classList.add(colores[score]);
    label.textContent = textos[score];

    verificarMatch();
});

// Verificar que las contraseñas coincidan
document.getElementById('passwordConfirm').addEventListener('input', verificarMatch);

function verificarMatch() {
    const pass    = document.getElementById('passwordInput').value;
    const confirm = document.getElementById('passwordConfirm').value;
    const msg     = document.getElementById('matchMsg');
    const btn     = document.querySelector('.btn-submit');

    if (!confirm) { msg.textContent = ''; return; }

    if (pass === confirm) {
        msg.textContent = '✓ Las contraseñas coinciden';
        msg.className   = 'match-msg match-ok';
        btn.disabled    = false;
    } else {
        msg.textContent = '✗ Las contraseñas no coinciden';
        msg.className   = 'match-msg match-error';
        btn.disabled    = true;
    }
}
</script>

</body>
</html>