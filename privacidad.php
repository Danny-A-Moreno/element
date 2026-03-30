<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .privacidad-container {
            max-width: 860px;
            margin: 3rem auto;
            padding: 0 2rem 4rem;
        }

        .privacidad-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .privacidad-header img {
            height: 60px;
            margin-bottom: 1.5rem;
            border-radius: 6px;
        }

        .privacidad-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 0.5rem;
        }

        .privacidad-header p {
            font-size: 0.9rem;
            color: #888;
        }

        /* Índice */
        .privacidad-index {
            background: #f8f8f8;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
        }

        .privacidad-index h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #888;
            margin-bottom: 1rem;
        }

        .privacidad-index ol {
            padding-left: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .privacidad-index a {
            font-size: 0.9rem;
            color: #333;
            text-decoration: none;
            transition: color 0.2s;
        }

        .privacidad-index a:hover {
            color: #000;
            text-decoration: underline;
        }

        /* Secciones */
        .seccion {
            margin-bottom: 2.5rem;
            scroll-margin-top: 2rem;
        }

        .seccion h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 1rem;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid #000;
        }

        .seccion h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #222;
            margin: 1.25rem 0 0.5rem;
        }

        .seccion p {
            font-size: 0.92rem;
            color: #444;
            line-height: 1.8;
            margin-bottom: 0.75rem;
        }

        .seccion ul {
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .seccion ul li {
            font-size: 0.92rem;
            color: #444;
            line-height: 1.8;
            margin-bottom: 0.3rem;
        }

        /* Destacado */
        .highlight-box {
            background: #f8f8f8;
            border-left: 3px solid #000;
            border-radius: 0 8px 8px 0;
            padding: 1rem 1.25rem;
            margin: 1rem 0;
        }

        .highlight-box p {
            margin: 0;
            font-size: 0.88rem;
            color: #555;
        }

        /* Tabla de datos */
        .datos-tabla {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.88rem;
        }

        .datos-tabla th {
            background: #000;
            color: #fff;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
        }

        .datos-tabla td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e0e0e0;
            color: #444;
            vertical-align: top;
        }

        .datos-tabla tr:last-child td {
            border-bottom: none;
        }

        .datos-tabla tr:nth-child(even) td {
            background: #f8f8f8;
        }

        /* Fecha */
        .fecha-actualizacion {
            text-align: center;
            font-size: 0.8rem;
            color: #aaa;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        /* Botón volver */
        .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            background: #000;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 2rem;
            transition: background 0.2s;
        }

        .btn-volver:hover {
            background: #333;
        }

        /* Badge legal */
        .badge-legal {
            display: inline-block;
            background: #000;
            color: #fff;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            margin-left: 0.5rem;
            vertical-align: middle;
            font-weight: 500;
        }

        @media (max-width: 600px) {
            .privacidad-container {
                padding: 0 1rem 3rem;
            }

            .privacidad-header h1 {
                font-size: 1.5rem;
            }

            .datos-tabla th,
            .datos-tabla td {
                padding: 0.5rem 0.75rem;
            }
        }
    </style>
</head>
<body style="background:#fff; color:#000;">

<div class="privacidad-container">

    <!-- HEADER -->
    <div class="privacidad-header">
        <img src="imagenes/logos/Element.jpg" alt="ELEMENT">
        <h1>Política de Privacidad</h1>
        <p>Última actualización: <?php echo date('d \d\e F \d\e Y'); ?></p>
    </div>

    <!-- ÍNDICE -->
    <div class="privacidad-index">
        <h3>Contenido</h3>
        <ol>
            <li><a href="#responsable">Responsable del tratamiento</a></li>
            <li><a href="#datos">Datos que recopilamos</a></li>
            <li><a href="#finalidad">Finalidad del tratamiento</a></li>
            <li><a href="#base-legal">Base legal</a></li>
            <li><a href="#comparticion">Compartición de datos</a></li>
            <li><a href="#derechos">Tus derechos</a></li>
            <li><a href="#seguridad">Seguridad de los datos</a></li>
            <li><a href="#cookies">Cookies</a></li>
            <li><a href="#contacto">Contacto</a></li>
        </ol>
    </div>

    <!-- INTRO -->
    <div class="seccion">
        <p>En <strong>ELEMENT</strong> nos comprometemos a proteger la privacidad de nuestros usuarios. Esta política explica cómo recopilamos, usamos y protegemos tu información personal, en cumplimiento de la <strong>Ley 1581 de 2012</strong> de protección de datos personales de Colombia y el Decreto 1377 de 2013.</p>
    </div>

    <!-- 1. RESPONSABLE -->
    <div class="seccion" id="responsable">
        <h2>1. Responsable del tratamiento</h2>
        <p>El responsable del tratamiento de tus datos personales es:</p>
        <ul>
            <li><strong>Razón social:</strong> ELEMENT Tiendas</li>
            <li><strong>País:</strong> Colombia</li>
            <li><strong>Correo de contacto:</strong> elementtiendas1@gmail.com</li>
            <li><strong>WhatsApp:</strong> +57 </li>
            <li><strong>Redes sociales:</strong> @elementtiendas</li>
        </ul>
    </div>

    <!-- 2. DATOS QUE RECOPILAMOS -->
    <div class="seccion" id="datos">
        <h2>2. Datos que recopilamos</h2>
        <p>Recopilamos únicamente los datos necesarios para prestarte nuestros servicios:</p>

        <table class="datos-tabla">
            <thead>
                <tr>
                    <th>Dato</th>
                    <th>Cuándo se recopila</th>
                    <th>Obligatorio</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Nombre y apellido</td>
                    <td>Al registrarse</td>
                    <td>Sí</td>
                </tr>
                <tr>
                    <td>Correo electrónico</td>
                    <td>Al registrarse</td>
                    <td>Sí</td>
                </tr>
                <tr>
                    <td>Contraseña (cifrada)</td>
                    <td>Al registrarse</td>
                    <td>Sí</td>
                </tr>
                <tr>
                    <td>Teléfono</td>
                    <td>Al realizar un pedido</td>
                    <td>Sí</td>
                </tr>
                <tr>
                    <td>Dirección de envío</td>
                    <td>Al realizar un pedido</td>
                    <td>Sí</td>
                </tr>
                <tr>
                    <td>Ciudad y barrio</td>
                    <td>Al realizar un pedido</td>
                    <td>Sí</td>
                </tr>
                <tr>
                    <td>Historial de compras</td>
                    <td>Al completar pedidos</td>
                    <td>Automático</td>
                </tr>
            </tbody>
        </table>

        <div class="highlight-box">
            <p>Las contraseñas se almacenan cifradas con bcrypt. ELEMENT nunca tiene acceso a tu contraseña en texto plano.</p>
        </div>
    </div>

    <!-- 3. FINALIDAD -->
    <div class="seccion" id="finalidad">
        <h2>3. Finalidad del tratamiento</h2>
        <p>Tus datos personales serán utilizados para los siguientes fines:</p>
        <ul>
            <li>Gestionar tu cuenta de usuario y autenticación.</li>
            <li>Procesar y entregar tus pedidos.</li>
            <li>Enviarte confirmaciones, actualizaciones y notificaciones de tus compras.</li>
            <li>Verificar tu identidad mediante código de verificación al registrarte.</li>
            <li>Atender solicitudes de cambios, devoluciones o soporte.</li>
            <li>Cumplir con obligaciones legales y fiscales.</li>
            <li>Mejorar la experiencia de compra en el sitio.</li>
        </ul>
        <p>ELEMENT <strong>no utilizará</strong> tus datos para enviar publicidad no solicitada ni para fines distintos a los descritos sin tu consentimiento previo.</p>
    </div>

    <!-- 4. BASE LEGAL -->
    <div class="seccion" id="base-legal">
        <h2>4. Base legal <span class="badge-legal">Ley 1581 de 2012</span></h2>
        <p>El tratamiento de tus datos personales se realiza bajo las siguientes bases legales:</p>
        <ul>
            <li><strong>Consentimiento:</strong> Al registrarte y aceptar esta política, autorizas expresamente el tratamiento de tus datos.</li>
            <li><strong>Ejecución de contrato:</strong> El tratamiento es necesario para gestionar tus pedidos y entregas.</li>
            <li><strong>Obligación legal:</strong> Algunos datos deben conservarse por disposición legal (facturación, registros comerciales).</li>
        </ul>
        <div class="highlight-box">
            <p>Puedes retirar tu consentimiento en cualquier momento contactándonos. La retirada del consentimiento no afectará la legalidad del tratamiento realizado antes de dicha retirada.</p>
        </div>
    </div>

    <!-- 5. COMPARTICIÓN -->
    <div class="seccion" id="comparticion">
        <h2>5. Compartición de datos</h2>
        <p>ELEMENT no vende ni alquila tus datos personales a terceros. Solo los compartimos en los siguientes casos:</p>
        <ul>
            <li><strong>Operadores logísticos:</strong> Para gestionar la entrega de tus pedidos (nombre, dirección, teléfono).</li>
            <li><strong>Pasarelas de pago:</strong> Para procesar transacciones de forma segura (Wompi, PSE).</li>
            <li><strong>Obligación legal:</strong> Cuando sea requerido por autoridades competentes colombianas.</li>
        </ul>
        <p>Todos los terceros con quienes compartimos datos están obligados contractualmente a proteger tu información y usarla únicamente para los fines indicados.</p>
    </div>

    <!-- 6. DERECHOS -->
    <div class="seccion" id="derechos">
        <h2>6. Tus derechos</h2>
        <p>Como titular de tus datos personales, tienes los siguientes derechos reconocidos por la Ley 1581 de 2012:</p>
        <ul>
            <li><strong>Conocer:</strong> Saber qué datos tuyos tenemos almacenados.</li>
            <li><strong>Actualizar:</strong> Corregir datos inexactos o desactualizados.</li>
            <li><strong>Rectificar:</strong> Solicitar corrección de información incorrecta.</li>
            <li><strong>Suprimir:</strong> Pedir la eliminación de tus datos cuando no sean necesarios.</li>
            <li><strong>Revocar:</strong> Retirar el consentimiento para el tratamiento.</li>
            <li><strong>Acceder:</strong> Solicitar una copia de los datos que tenemos sobre ti.</li>
        </ul>

        <h3>¿Cómo ejercer tus derechos?</h3>
        <p>Puedes ejercer cualquiera de estos derechos contactándonos por:</p>
        <ul>
            <li>Correo: <strong>elementtiendas1@gmail.com</strong></li>
            <li>WhatsApp: <strong>+57 </strong></li>
        </ul>
        <p>Responderemos tu solicitud en un plazo máximo de <strong>10 días hábiles</strong>.</p>

        <div class="highlight-box">
            <p>Si consideras que tus derechos no han sido atendidos, puedes presentar una queja ante la <strong>Superintendencia de Industria y Comercio (SIC)</strong> en <a href="https://www.sic.gov.co" target="_blank" style="color:#000;">www.sic.gov.co</a>.</p>
        </div>
    </div>

    <!-- 7. SEGURIDAD -->
    <div class="seccion" id="seguridad">
        <h2>7. Seguridad de los datos</h2>
        <p>ELEMENT implementa medidas técnicas y organizativas para proteger tus datos personales contra accesos no autorizados, pérdida o alteración:</p>
        <ul>
            <li>Contraseñas almacenadas con cifrado bcrypt.</li>
            <li>Verificación de correo electrónico al registrarse.</li>
            <li>Acceso restringido a la base de datos solo para personal autorizado.</li>
            <li>Uso de HTTPS para la transmisión segura de datos.</li>
            <li>Códigos de verificación con expiración de 15 minutos.</li>
        </ul>
        <p>Sin embargo, ningún sistema es 100% seguro. En caso de una brecha de seguridad que afecte tus datos, te notificaremos a la brevedad posible.</p>
    </div>

    <!-- 8. COOKIES -->
    <div class="seccion" id="cookies">
        <h2>8. Cookies</h2>
        <p>ELEMENT utiliza cookies de sesión estrictamente necesarias para el funcionamiento del sitio. Estas cookies:</p>
        <ul>
            <li>Mantienen tu sesión iniciada mientras navegas.</li>
            <li>Guardan temporalmente el contenido de tu carrito de compras.</li>
            <li>No rastrean tu actividad fuera del sitio.</li>
            <li>Se eliminan automáticamente al cerrar el navegador.</li>
        </ul>
        <p>No utilizamos cookies de publicidad ni de rastreo de terceros.</p>
    </div>

    <!-- 9. CONTACTO -->
    <div class="seccion" id="contacto">
        <h2>9. Contacto</h2>
        <p>Para cualquier consulta relacionada con esta política de privacidad o el tratamiento de tus datos, contáctanos:</p>
        <ul>
            <li><strong>Correo:</strong> elementtiendas1@gmail.com</li>
            <li><strong>WhatsApp:</strong> +57 </li>
            <li><strong>Instagram:</strong> @elementtiendas</li>
            <li><strong>Facebook:</strong> ELEMENT Tiendas</li>
        </ul>
    </div>

    <!-- FECHA -->
    <p class="fecha-actualizacion">
        Esta política de privacidad fue actualizada el <?php echo date('d \d\e F \d\e Y'); ?>
        y aplica para todos los usuarios de ELEMENT en Colombia.<br>
        © <?php echo date('Y'); ?> ELEMENT Tiendas · Todos los derechos reservados.
    </p>

</div>

</body>
</html>