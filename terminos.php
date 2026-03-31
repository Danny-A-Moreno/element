<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        .terminos-container {
            max-width: 860px;
            margin: 3rem auto;
            padding: 0 2rem 4rem;
        }

        .terminos-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .terminos-header img {
            height: 60px;
            margin-bottom: 1.5rem;
            border-radius: 6px;
        }

        .terminos-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 0.5rem;
        }

        .terminos-header p {
            font-size: 0.9rem;
            color: #888;
        }

        /* Índice */
        .terminos-index {
            background: #f8f8f8;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
        }

        .terminos-index h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #888;
            margin-bottom: 1rem;
        }

        .terminos-index ol {
            padding-left: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .terminos-index a {
            font-size: 0.9rem;
            color: #333;
            text-decoration: none;
            transition: color 0.2s;
        }

        .terminos-index a:hover {
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
            display: flex;
            align-items: center;
            gap: 0.6rem;
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

        @media (max-width: 600px) {
            .terminos-container {
                padding: 0 1rem 3rem;
            }
            .terminos-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body style="background:#fff; color:#000;">

<div class="terminos-container">

    <!-- HEADER -->
    <div class="terminos-header">
        <img src="imagenes/logos/Element.jpg" alt="ELEMENT">
        <h1>Términos y Condiciones</h1>
        <p>Última actualización: <?php echo date('d \d\e F \d\e Y'); ?></p>
    </div>

    <!-- ÍNDICE -->
    <div class="terminos-index">
        <h3>Contenido</h3>
        <ol>
            <li><a href="#uso">Uso del sitio web</a></li>
            <li><a href="#compras">Compras y pagos</a></li>
            <li><a href="#envios">Envíos y devoluciones</a></li>
            <li><a href="#datos">Protección de datos personales</a></li>
        </ol>
    </div>

    <!-- 1. USO DEL SITIO -->
    <div class="seccion" id="uso">
        <h2>1. Uso del sitio web</h2>

        <p>Al acceder y utilizar el sitio web de <strong>ELEMENT</strong>, el usuario acepta cumplir con los presentes términos y condiciones. Si no está de acuerdo con alguno de ellos, debe abstenerse de utilizar nuestros servicios.</p>

        <h3>1.1 Registro de cuenta</h3>
        <p>Para realizar compras en ELEMENT es necesario crear una cuenta. El usuario se compromete a:</p>
        <ul>
            <li>Proporcionar información verídica, completa y actualizada.</li>
            <li>Mantener la confidencialidad de su contraseña.</li>
            <li>Notificar a ELEMENT ante cualquier uso no autorizado de su cuenta.</li>
            <li>Ser mayor de edad o contar con autorización de un adulto responsable.</li>
        </ul>

        <h3>1.2 Uso permitido</h3>
        <p>El sitio web de ELEMENT es exclusivamente para uso personal y no comercial. Está prohibido:</p>
        <ul>
            <li>Reproducir, distribuir o modificar el contenido del sitio sin autorización.</li>
            <li>Utilizar el sitio para actividades fraudulentas o ilegales.</li>
            <li>Intentar acceder a sistemas o datos no autorizados.</li>
        </ul>

        <div class="highlight-box">
            <p>ELEMENT se reserva el derecho de suspender o cancelar cuentas que incumplan estos términos, sin previo aviso y sin responsabilidad alguna.</p>
        </div>
    </div>

    <!-- 2. COMPRAS Y PAGOS -->
    <div class="seccion" id="compras">
        <h2>2. Compras y pagos</h2>

        <h3>2.1 Proceso de compra</h3>
        <p>Al realizar un pedido en ELEMENT, el usuario confirma que la información de pago y envío proporcionada es correcta. El pedido se considera confirmado una vez que ELEMENT envíe la notificación de confirmación al correo registrado.</p>

        <h3>2.2 Precios</h3>
        <p>Todos los precios publicados en el sitio están expresados en pesos colombianos (COP) e incluyen IVA cuando aplique. ELEMENT se reserva el derecho de modificar los precios sin previo aviso, pero los pedidos confirmados se respetarán al precio acordado en el momento de la compra.</p>

        <h3>2.3 Métodos de pago aceptados</h3>
        <ul>
            <li>Pago contraentrega (efectivo al recibir el pedido)</li>
            <li>Tarjetas de crédito y débito (Visa, Mastercard)</li>
            <li>PSE (Pagos Seguros en Línea)</li>
            <li>SisteCredito</li>
        </ul>

        <h3>2.4 Disponibilidad de productos</h3>
        <p>La disponibilidad de los productos está sujeta al stock existente. En caso de que un producto no esté disponible después de confirmar el pedido, ELEMENT contactará al cliente para ofrecer una alternativa o realizar el reembolso correspondiente.</p>

        <div class="highlight-box">
            <p>De conformidad con la <strong>Ley 1480 de 2011</strong> (Estatuto del Consumidor de Colombia), el consumidor tiene derecho a retracto dentro de los 5 días hábiles siguientes a la entrega del producto, siempre que este no haya sido usado.</p>
        </div>
    </div>

    <!-- 3. ENVÍOS Y DEVOLUCIONES -->
    <div class="seccion" id="envios">
        <h2>3. Envíos y devoluciones</h2>

        <h3>3.1 Cobertura de envíos</h3>
        <p>ELEMENT realiza envíos a todo el territorio colombiano. Los tiempos de entrega estimados son:</p>
        <ul>
            <li><strong>Ciudad principal</strong> (Bogotá D.C): 2 a 4 días hábiles.</li>
            <li><strong>Municipios y zonas rurales:</strong> 4 a 8 días hábiles.</li>
        </ul>
        <p>Los tiempos de entrega son aproximados y pueden variar por causas externas a ELEMENT (fuerza mayor, festivos, condiciones climáticas).</p>

        <h3>3.2 Costo de envío</h3>
        <p>El costo del envío se calcula al momento del checkout según la ciudad de destino. ELEMENT podrá ofrecer envío gratuito en promociones especiales, lo cual se indicará claramente en el sitio.</p>

        <h3>3.3 Devoluciones y cambios</h3>
        <p>El cliente podrá solicitar cambio o devolución en los siguientes casos:</p>
        <ul>
            <li>Producto defectuoso o en mal estado al recibirlo.</li>
            <li>Producto diferente al pedido (talla, color o referencia incorrecta).</li>
            <li>Ejercicio del derecho de retracto dentro de los 5 días hábiles siguientes a la entrega.</li>
        </ul>

        <h3>3.4 Condiciones para devolución</h3>
        <ul>
            <li>El producto debe estar en su estado original, sin uso, lavado ni daños.</li>
            <li>Debe conservar las etiquetas y empaque original.</li>
            <li>El cliente debe contactar a ELEMENT a través de WhatsApp o correo antes de enviar el producto.</li>
        </ul>

        <div class="highlight-box">
            <p>Para iniciar un proceso de cambio o devolución, contáctanos por WhatsApp al <strong>+57 </strong> o escríbenos a nuestras redes sociales @elementtiendas.</p>
        </div>
    </div>

    <!-- 4. PROTECCIÓN DE DATOS -->
    <div class="seccion" id="datos">
        <h2>4. Protección de datos personales</h2>

        <p>En cumplimiento de la <strong>Ley 1581 de 2012</strong> y el Decreto 1377 de 2013 sobre protección de datos personales en Colombia, ELEMENT informa:</p>

        <h3>4.1 Datos que recopilamos</h3>
        <ul>
            <li>Nombre y apellido</li>
            <li>Correo electrónico</li>
            <li>Dirección de envío y ciudad</li>
            <li>Número de teléfono</li>
            <li>Historial de compras</li>
        </ul>

        <h3>4.2 Finalidad del tratamiento</h3>
        <p>Los datos personales recopilados serán utilizados exclusivamente para:</p>
        <ul>
            <li>Gestionar pedidos y envíos.</li>
            <li>Enviar notificaciones relacionadas con las compras.</li>
            <li>Mejorar la experiencia de compra en el sitio.</li>
            <li>Cumplir con obligaciones legales y comerciales.</li>
        </ul>

        <h3>4.3 Derechos del titular</h3>
        <p>Como titular de sus datos personales, usted tiene derecho a:</p>
        <ul>
            <li>Conocer, actualizar y rectificar sus datos.</li>
            <li>Solicitar la supresión de sus datos cuando no sean necesarios.</li>
            <li>Revocar la autorización otorgada para el tratamiento.</li>
            <li>Presentar quejas ante la Superintendencia de Industria y Comercio (SIC).</li>
        </ul>

        <h3>4.4 Compartición de datos</h3>
        <p>ELEMENT no venderá, alquilará ni compartirá los datos personales de sus clientes con terceros, salvo en los casos estrictamente necesarios para la prestación del servicio (operadores logísticos, pasarelas de pago) o cuando lo exija la ley.</p>

        <div class="highlight-box">
            <p>Para ejercer sus derechos como titular de datos personales, puede contactarnos en <strong>elementtiendas1@gmail.com</strong> o a través de nuestros canales oficiales.</p>
        </div>
    </div>

    <!-- FECHA -->
    <p class="fecha-actualizacion">
        Estos términos y condiciones fueron actualizados el <?php echo date('d \d\e F \d\e Y'); ?> y rigen para todos los usuarios de ELEMENT en Colombia.<br>
        © <?php echo date('Y'); ?> ELEMENT Tiendas · Todos los derechos reservados.
    </p>

</div>

</body>
</html>