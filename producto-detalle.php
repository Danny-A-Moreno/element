<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/BaseDatos.php';
$db = new BaseDatos();

// Verificar que se recibió un ID de producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: catalogo.php');
    exit();
}

$id_producto = intval($_GET['id']);

// ===== OBTENER INFORMACIÓN DEL PRODUCTO =====
try {
    // Producto principal
    $sqlProducto = "SELECT * FROM productos WHERE id_producto = :id AND estado = 'activo'";
    $producto = $db->query($sqlProducto, ['id' => $id_producto])->fetch();
    
    if (!$producto) {
        $_SESSION['error'] = "Producto no encontrado";
        header('Location: catalogo.php');
        exit();
    }
    
    // Imágenes del producto
    $sqlImagenes = "SELECT * FROM producto_imagenes 
                    WHERE id_producto = :id 
                    ORDER BY es_principal DESC, orden ASC";
    $imagenes = $db->query($sqlImagenes, ['id' => $id_producto])->fetchAll();
    
    // Colores disponibles
    $sqlColores = "SELECT DISTINCT color 
                   FROM productos_variante 
                   WHERE id_producto = :id AND estado = 'activo' AND stock > 0";
    $colores = $db->query($sqlColores, ['id' => $id_producto])->fetchAll();
    
    // Tallas disponibles (se actualizarán dinámicamente con JS según el color)
    $sqlTallas = "SELECT DISTINCT talla 
                  FROM productos_variante 
                  WHERE id_producto = :id AND estado = 'activo' AND stock > 0
                  ORDER BY FIELD(talla, 'XS', 'S', 'M', 'L', 'XL', 'XXL')";
    $tallas = $db->query($sqlTallas, ['id' => $id_producto])->fetchAll();
    
    // Todas las variantes (para JavaScript)
    $sqlVariantes = "SELECT id_variante, color, talla, stock, estado 
                     FROM productos_variante 
                     WHERE id_producto = :id";
    $variantes = $db->query($sqlVariantes, ['id' => $id_producto])->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error al cargar el producto";
    header('Location: catalogo.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - ELEMENT</title>
    <link rel="icon" type="image/png" href="imagenes/logos/Element.ico">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== ESTILOS PARA DETALLE DE PRODUCTO ===== */
        .producto-detalle-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
        }

        /* ===== GALERÍA DE IMÁGENES ===== */
        .galeria-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .imagen-principal {
            width: 100%;
            height: 600px;
            background: #f5f5f5;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .imagen-principal img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .imagen-principal img:hover {
            transform: scale(1.05);
        }

        .miniaturas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.5rem;
        }

        .miniatura {
            height: 100px;
            background: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .miniatura.active {
            border-color: #000;
        }

        .miniatura:hover {
            border-color: #666;
        }

        .miniatura img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ===== INFORMACIÓN DEL PRODUCTO ===== */
        .producto-info-container {
            padding: 1rem 0;
        }

        .producto-badge {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .producto-titulo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #000;
        }

        .producto-precio {
            font-size: 2rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 1.5rem;
        }

        .producto-descripcion {
            font-size: 1rem;
            line-height: 1.6;
            color: #666;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        /* ===== SELECTORES DE VARIANTES ===== */
        .selector-grupo {
            margin-bottom: 2rem;
        }

        .selector-label {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            display: block;
            color: #000;
        }

        .opciones-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .opcion-btn {
            padding: 0.8rem 1.5rem;
            border: 2px solid #ddd;
            background: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #000;
        }

        .opcion-btn:hover {
            border-color: #000;
        }

        .opcion-btn.active {
            background: #000;
            color: #fff;
            border-color: #000;
        }

        .opcion-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .opcion-btn:disabled:hover {
            border-color: #ddd;
        }

        /* ===== CANTIDAD Y ACCIONES ===== */
        .cantidad-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .cantidad-label {
            font-weight: 600;
            color: #000;
        }

        .cantidad-control {
            display: flex;
            align-items: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .cantidad-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: #f5f5f5;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 700;
            transition: background 0.2s ease;
        }

        .cantidad-btn:hover {
            background: #e0e0e0;
        }

        .cantidad-input {
            width: 60px;
            height: 40px;
            border: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
        }

        .stock-info {
            font-size: 0.9rem;
            color: #666;
        }

        .stock-info.bajo {
            color: #ff9800;
            font-weight: 600;
        }

        .acciones-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn-agregar-carrito {
            width: 100%;
            padding: 1.2rem;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .btn-agregar-carrito:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .btn-agregar-carrito:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .mensaje-error {
            background: #ff5252;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }

        .mensaje-error.show {
            display: block;
        }

        .mensaje-exito {
            background: #4CAF50;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }

        .mensaje-exito.show {
            display: block;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .producto-detalle-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 1rem;
            }

            .imagen-principal {
                height: 400px;
            }

            .producto-titulo {
                font-size: 1.8rem;
            }

            .producto-precio {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- ===== HEADER  ===== -->
<header class="main-header">
    <div class="header-content">

        <!-- LOGO - vuelve al inicio -->
        <a href="index.php" class="logo-link">
            <img src="imagenes/logos/Element.jpg" alt="Logo" class="logo">
        </a>

        <!-- Menú desplegable de categorías dinámico -->
        <div class="dropdown">
            <a href="catalogo.php" class="nav-link">Productos</a>
        </div>
            
            <a href="#quienes-somos" class="nav-link">Quienes somos</a>

        </nav>

    <!-- ICONOS A LA DERECHA -->
    <div class="nav-icons">
        <!-- Logo Usuario -->
        <div class="dropdown">

<?php if (isset($_SESSION['id_usuario'])): ?>
    <!-- Usuario está logueado -->
    
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN'): ?>
        <!-- MENÚ ADMIN -->
        <a href="#" class="icon-link">
            <img src="imagenes/logos/profile.png" alt="Mi cuenta">
        </a>
        <div class="dropdown-content">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/productos.php">Productos</a>
            <a href="/admin/pedidos.php">Pedidos</a>
            <a href="logout.php">Cerrar sesión</a>
        </div>
        
    <?php else: ?>
        <!-- MENÚ USUARIO NORMAL -->
        <a href="#" class="icon-link">
            <img src="imagenes/logos/profile.png" alt="Mi cuenta">
        </a>
        <div class="dropdown-content">
            <a href="perfil.php">Configuración</a>
            <a href="mis-pedidos.php">Mis pedidos</a>

            <a href="logout.php">Cerrar sesión</a>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <!-- USUARIO NO LOGUEADO -->
    <a href="#" class="icon-link" id="btn-login">
        <img src="imagenes/logos/profile.png" alt="Iniciar sesión">
    </a>
<?php endif; ?>

            </div>



            <!-- Imagen carrito -->
            <a href="carrito.php" class="icon-link">
                <img src="imagenes/logos/cart.png" alt="Carrito">
            </a>

        <form action="buscar.php" method="GET" class="search-box">
            <img class="search-icon" src="imagenes/logos/Lupa.png" alt="Buscar">
            <input type="text" 
                name="q" 
                class="search-input" 
                placeholder="Buscar productos..." 
                required>
        </form>
        </div>
    </div>
</header>


<!-- ===== CONTENIDO PRINCIPAL ===== -->
<div class="producto-detalle-container">
    
    <!-- ===== GALERÍA DE IMÁGENES ===== -->
    <div class="galeria-container">
        <div class="imagen-principal" id="imagenPrincipal">
            <?php if (!empty($imagenes)): ?>
                <img src="imagenes/productos/<?php echo htmlspecialchars($imagenes[0]['nombre_archivo']); ?>" 
                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
            <?php else: ?>
                <img src="imagenes/placeholder.png" alt="Sin imagen">
            <?php endif; ?>
        </div>

        <?php if (count($imagenes) > 1): ?>
        <div class="miniaturas">
            <?php foreach ($imagenes as $index => $imagen): ?>
                <div class="miniatura <?php echo $index === 0 ? 'active' : ''; ?>" 
                     onclick="cambiarImagen('<?php echo htmlspecialchars($imagen['nombre_archivo']); ?>', this)">
                    <img src="imagenes/productos/<?php echo htmlspecialchars($imagen['nombre_archivo']); ?>" 
                         alt="Miniatura <?php echo $index + 1; ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== INFORMACIÓN Y SELECCIÓN ===== -->
    <div class="producto-info-container">
        <span class="producto-badge">NUEVO</span>
        
        <h1 class="producto-titulo"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
        
        <div class="producto-precio">
            $<?php echo number_format($producto['precio'], 0, ',', '.'); ?>
        </div>

        <?php if ($producto['descripcion']): ?>
        <div class="producto-descripcion">
            <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
        </div>
        <?php endif; ?>

        <!-- Mensajes -->
        <div id="mensajeError" class="mensaje-error"></div>
        <div id="mensajeExito" class="mensaje-exito"></div>

        <!-- ===== SELECTOR DE COLOR ===== -->
        <?php if (!empty($colores)): ?>
        <div class="selector-grupo">
            <label class="selector-label">
                Color: <span id="colorSeleccionado">Selecciona un color</span>
            </label>
            <div class="opciones-grid" id="coloresGrid">
                <?php foreach ($colores as $color): ?>
                    <button type="button" 
                            class="opcion-btn" 
                            data-color="<?php echo htmlspecialchars($color['color']); ?>"
                            onclick="seleccionarColor('<?php echo htmlspecialchars($color['color']); ?>')">
                        <?php echo htmlspecialchars($color['color']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== SELECTOR DE TALLA ===== -->
        <div class="selector-grupo">
            <label class="selector-label">
                Talla: <span id="tallaSeleccionada">Selecciona una talla</span>
            </label>
            <div class="opciones-grid" id="tallasGrid">
                <!-- Se llenarán dinámicamente con JavaScript -->
            </div>
        </div>

        <!-- ===== CANTIDAD ===== -->
        <div class="cantidad-container">
            <span class="cantidad-label">Cantidad:</span>
            <div class="cantidad-control">
                <button type="button" class="cantidad-btn" onclick="cambiarCantidad(-1)">−</button>
                <input type="number" id="cantidad" class="cantidad-input" value="1" min="1" readonly>
                <button type="button" class="cantidad-btn" onclick="cambiarCantidad(1)">+</button>
            </div>
            <span id="stockInfo" class="stock-info"></span>
        </div>

        <!-- ===== ACCIONES ===== -->
        <div class="acciones-container">
            <button type="button" 
                    id="btnAgregarCarrito" 
                    class="btn-agregar-carrito" 
                    onclick="agregarAlCarrito()"
                    disabled>
                Agregar al Carrito
            </button>
        </div>
    </div>
</div>

<!-- ===== DATOS PARA JAVASCRIPT ===== -->
<script>
// Variantes del producto
const variantes = <?php echo json_encode($variantes); ?>;
const idProducto = <?php echo $id_producto; ?>;
const precioBase = <?php echo $producto['precio']; ?>;

let colorSeleccionado = null;
let tallaSeleccionada = null;
let varianteActual = null;
let stockDisponible = 0;

// ===== CAMBIAR IMAGEN PRINCIPAL =====
function cambiarImagen(nombreArchivo, elemento) {
    const imagenPrincipal = document.querySelector('#imagenPrincipal img');
    imagenPrincipal.src = 'imagenes/productos/' + nombreArchivo;
    
    document.querySelectorAll('.miniatura').forEach(mini => mini.classList.remove('active'));
    elemento.classList.add('active');
}

// ===== SELECCIONAR COLOR =====
function seleccionarColor(color) {
    colorSeleccionado = color;
    
    // Actualizar UI
    document.querySelectorAll('[data-color]').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    document.getElementById('colorSeleccionado').textContent = color;
    
    // Actualizar tallas disponibles para este color
    actualizarTallasDisponibles();
    
    // Reset talla seleccionada
    tallaSeleccionada = null;
    document.getElementById('tallaSeleccionada').textContent = 'Selecciona una talla';
    
    verificarSeleccion();
}

// ===== ACTUALIZAR TALLAS DISPONIBLES =====
function actualizarTallasDisponibles() {
    const tallasGrid = document.getElementById('tallasGrid');
    tallasGrid.innerHTML = '';
    
    // Obtener tallas únicas para el color seleccionado
    const tallasDisponibles = {};
    variantes.forEach(variante => {
        if (variante.color === colorSeleccionado && variante.stock > 0 && variante.estado === 'activo') {
            tallasDisponibles[variante.talla] = true;
        }
    });
    
    // Orden de tallas
    const ordenTallas = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    const tallasEncontradas = variantes
        .filter(v => v.color === colorSeleccionado)
        .map(v => v.talla)
        .filter((talla, index, self) => self.indexOf(talla) === index);
    
    const tallasOrdenadas = ordenTallas.filter(t => tallasEncontradas.includes(t));
    
    tallasOrdenadas.forEach(talla => {
        const disponible = tallasDisponibles[talla] || false;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'opcion-btn';
        btn.textContent = talla;
        btn.dataset.talla = talla;
        btn.disabled = !disponible;
        btn.onclick = () => seleccionarTalla(talla);
        tallasGrid.appendChild(btn);
    });
}

// ===== SELECCIONAR TALLA =====
function seleccionarTalla(talla) {
    tallaSeleccionada = talla;
    
    // Actualizar UI
    document.querySelectorAll('[data-talla]').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    document.getElementById('tallaSeleccionada').textContent = talla;
    
    // Encontrar variante exacta
    varianteActual = variantes.find(v => 
        v.color === colorSeleccionado && 
        v.talla === tallaSeleccionada &&
        v.estado === 'activo'
    );
    
    if (varianteActual) {
        stockDisponible = varianteActual.stock;
        actualizarStockInfo();
    }
    
    verificarSeleccion();
}

// ===== ACTUALIZAR INFO DE STOCK =====
function actualizarStockInfo() {
    const stockInfo = document.getElementById('stockInfo');
    
    if (stockDisponible > 0) {
        if (stockDisponible <= 5) {
            stockInfo.textContent = `¡Solo quedan ${stockDisponible} unidades!`;
            stockInfo.className = 'stock-info bajo';
        } else {
            stockInfo.textContent = `${stockDisponible} unidades disponibles`;
            stockInfo.className = 'stock-info';
        }
    } else {
        stockInfo.textContent = 'Sin stock';
        stockInfo.className = 'stock-info';
    }
}

// ===== VERIFICAR SELECCIÓN COMPLETA =====
function verificarSeleccion() {
    const btnAgregar = document.getElementById('btnAgregarCarrito');
    
    if (colorSeleccionado && tallaSeleccionada && stockDisponible > 0) {
        btnAgregar.disabled = false;
    } else {
        btnAgregar.disabled = true;
    }
}

// ===== CAMBIAR CANTIDAD =====
function cambiarCantidad(delta) {
    const inputCantidad = document.getElementById('cantidad');
    let cantidad = parseInt(inputCantidad.value) || 1;
    
    cantidad += delta;
    
    if (cantidad < 1) cantidad = 1;
    if (cantidad > stockDisponible) cantidad = stockDisponible;
    
    inputCantidad.value = cantidad;
}

// ===== AGREGAR AL CARRITO =====
function agregarAlCarrito() {
    if (!varianteActual) {
        mostrarError('Por favor selecciona color y talla');
        return;
    }
    
    const cantidad = parseInt(document.getElementById('cantidad').value);
    
    if (cantidad > stockDisponible) {
        mostrarError('No hay suficiente stock disponible');
        return;
    }
    
    // Enviar al servidor
    fetch('carrito-agregar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_variante: varianteActual.id_variante,
            cantidad: cantidad
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarExito('¡Producto agregado al carrito!');
            
            // Opcional: redirigir después de 2 segundos
            setTimeout(() => {
                window.location.href = 'carrito.php';
            }, 2000);
        } else {
            mostrarError(data.message || 'Error al agregar al carrito');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al agregar al carrito');
    });
}

// ===== MOSTRAR MENSAJES =====
function mostrarError(mensaje) {
    const div = document.getElementById('mensajeError');
    div.textContent = mensaje;
    div.classList.add('show');
    setTimeout(() => div.classList.remove('show'), 5000);
}

function mostrarExito(mensaje) {
    const div = document.getElementById('mensajeExito');
    div.textContent = mensaje;
    div.classList.add('show');
    setTimeout(() => div.classList.remove('show'), 3000);
}
</script>

</body>
</html>