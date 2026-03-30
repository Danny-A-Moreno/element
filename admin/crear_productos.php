<?php
session_start();

// Protección: solo admin puede acceder
if (!isset($_SESSION['id_usuario']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../BaseDatos.php';
$db = new BaseDatos();

// SOLO MODO CREAR (no acepta edición)
$modo = 'crear';

// Si alguien intenta acceder con ?id=, redirigir a producto_form.php (edición)
if (isset($_GET['id'])) {
    header('Location: producto_form.php?id=' . $_GET['id']);
    exit();
}

// PROCESAR CREACIÓN DE PRODUCTO CON VARIANTES E IMÁGENES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $estado = $_POST['estado'] ?? 'activo';
    $categoria = $_POST['categoria'] ?? ''; // ⭐ NUEVO: Obtener categoría
    
    // Obtener colores y tallas seleccionados
    $colores = $_POST['colores'] ?? [];
    $tallasSeleccionadas = $_POST['tallas'] ?? [];
    $stockPorVariante = $_POST['stock'] ?? [];
    
    // Validaciones
    if (empty($nombre)) {
        $_SESSION['error'] = "El nombre del producto es obligatorio";
        header('Location: crear_productos.php');
        exit();
    }
    
    if ($precio <= 0) {
        $_SESSION['error'] = "El precio debe ser mayor a 0";
        header('Location: crear_productos.php');
        exit();
    }
    
    // ⭐ NUEVA VALIDACIÓN: Categoría obligatoria
    if (empty($categoria) || !in_array($categoria, ['mujer', 'hombre', 'unisex'])) {
        $_SESSION['error'] = "Debes seleccionar una categoría válida (Mujer, Hombre o Unisex)";
        header('Location: crear_productos.php');
        exit();
    }
    
    if (empty($colores)) {
        $_SESSION['error'] = "Debes seleccionar al menos un color";
        header('Location: crear_productos.php');
        exit();
    }
    
    if (empty($tallasSeleccionadas)) {
        $_SESSION['error'] = "Debes seleccionar al menos una talla";
        header('Location: crear_productos.php');
        exit();
    }
    
    // Validar imagen principal obligatoria
    if (!isset($_FILES['imagen_principal']) || $_FILES['imagen_principal']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['error'] = "La imagen principal es obligatoria";
        header('Location: crear_productos.php');
        exit();
    }
    
    try {
        // 1. Crear el producto 
        $sql = "INSERT INTO productos (nombre, descripcion, precio, categoria, estado, fecha_creacion, fecha_actualizacion) 
                VALUES (:nombre, :descripcion, :precio, :categoria, :estado, NOW(), NOW())";
        
        $db->query($sql, [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'categoria' => $categoria, // ⭐ NUEVO
            'estado' => $estado
        ]);
        
        $idProducto = $db->getLastInsertId();
        
        // 2. Crear carpeta de imágenes si no existe
        $dirImagenes = __DIR__ . '/../imagenes/productos';
        if (!is_dir($dirImagenes)) {
            mkdir($dirImagenes, 0777, true);
        }
        
        // 3. Subir imagen principal
        $imagenPrincipal = $_FILES['imagen_principal'];
        $extension = strtolower(pathinfo($imagenPrincipal['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($extension, $permitidas)) {
            throw new Exception("Formato de imagen no permitido. Use: JPG, PNG o WEBP");
        }
        
        $nombreArchivo = 'producto_' . $idProducto . '_principal_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $rutaDestino = $dirImagenes . '/' . $nombreArchivo;
        
        if (move_uploaded_file($imagenPrincipal['tmp_name'], $rutaDestino)) {
            $sqlImagen = "INSERT INTO producto_imagenes (id_producto, nombre_archivo, es_principal, orden) 
                         VALUES (:id_producto, :nombre_archivo, 1, 0)";
            $db->query($sqlImagen, [
                'id_producto' => $idProducto,
                'nombre_archivo' => $nombreArchivo
            ]);
        }
        
        // 4. Subir galería de imágenes (opcional)
        if (isset($_FILES['galeria']) && is_array($_FILES['galeria']['name'])) {
            $orden = 1;
            
            foreach ($_FILES['galeria']['name'] as $key => $name) {
                if ($_FILES['galeria']['error'][$key] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($extension, $permitidas)) {
                        $nombreArchivo = 'producto_' . $idProducto . '_galeria_' . $orden . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                        $rutaDestino = $dirImagenes . '/' . $nombreArchivo;
                        
                        if (move_uploaded_file($_FILES['galeria']['tmp_name'][$key], $rutaDestino)) {
                            $sqlImagen = "INSERT INTO producto_imagenes (id_producto, nombre_archivo, es_principal, orden) 
                                         VALUES (:id_producto, :nombre_archivo, 0, :orden)";
                            $db->query($sqlImagen, [
                                'id_producto' => $idProducto,
                                'nombre_archivo' => $nombreArchivo,
                                'orden' => $orden
                            ]);
                            $orden++;
                        }
                    }
                }
            }
        }
        
        // 5. Crear variantes (combinación de color × talla)
        $variantesCreadas = 0;
        
        foreach ($colores as $color) {
            foreach ($tallasSeleccionadas as $talla) {
                $stock = intval($stockPorVariante[$color][$talla] ?? 0);
                
                $sqlVariante = "INSERT INTO productos_variante (id_producto, color, talla, stock, estado) 
                               VALUES (:id_producto, :color, :talla, :stock, 'activo')";
                
                $db->query($sqlVariante, [
                    'id_producto' => $idProducto,
                    'color' => $color,
                    'talla' => $talla,
                    'stock' => $stock
                ]);
                
                $variantesCreadas++;
            }
        }

        $categorias_texto = [
            'mujer' => 'Mujer',
            'hombre' => 'Hombre',
            'unisex' => 'Unisex'
        ];
        
        $_SESSION['success'] = "Producto '$nombre' creado exitosamente en categoría '{$categorias_texto[$categoria]}' con $variantesCreadas variantes";
        header("Location: productos.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al crear producto: " . $e->getMessage();
        header('Location: crear_productos.php');
        exit();
    }
}

// Colores y tallas predefinidos
$coloresPredefinidos = ['Negro', 'Blanco', 'Azul', 'Rojo', 'Gris', 'Verde', 'Amarillo', 'Rosado'];
$tallasPredefinidas = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Producto - ELEMENT</title>
    <link rel="icon" type="image/png" href="../imagenes/Element.ico">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="productos.css">
    <style>
        .wizard-container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--medium-grey);
            border: 1px solid var(--border-grey);
            border-radius: 12px;
            padding: 2rem;
        }

        .wizard-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }

        .wizard-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: var(--border-grey);
            z-index: 0;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--dark-grey);
            border: 2px solid var(--border-grey);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--text-grey);
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: var(--accent-gold);
            border-color: var(--accent-gold);
            color: var(--primary-black);
        }

        .step.completed .step-number {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .step-label {
            font-size: 0.85rem;
            color: var(--text-grey);
        }

        .step.active .step-label {
            color: var(--accent-gold);
            font-weight: 600;
        }

        .wizard-content {
            display: none;
        }

        .wizard-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        .section-title {
            color: var(--primary-white);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .color-option {
            position: relative;
        }

        .color-option input[type="checkbox"] {
            display: none;
        }

        .color-label {
            display: block;
            padding: 1rem;
            background: var(--dark-grey);
            border: 2px solid var(--border-grey);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--primary-white);
            font-weight: 500;
        }

        .color-option input[type="checkbox"]:checked + .color-label {
            background: var(--accent-gold);
            border-color: var(--accent-gold);
            color: var(--primary-black);
            transform: scale(1.05);
        }

        .custom-color-input {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .talla-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .talla-option {
            position: relative;
        }

        .talla-option input[type="checkbox"] {
            display: none;
        }

        .talla-label {
            display: block;
            padding: 1.2rem;
            background: var(--dark-grey);
            border: 2px solid var(--border-grey);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--primary-white);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .talla-option input[type="checkbox"]:checked + .talla-label {
            background: var(--accent-gold);
            border-color: var(--accent-gold);
            color: var(--primary-black);
        }

        .stock-matrix {
            margin-top: 2rem;
        }

        .stock-row {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--dark-grey);
            border-radius: 8px;
        }

        .stock-row-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent-gold);
            margin-bottom: 1rem;
        }

        .stock-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
        }

        .stock-input-group {
            display: flex;
            flex-direction: column;
        }

        .stock-input-group label {
            color: var(--text-grey);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .stock-input-group input {
            padding: 0.6rem;
            background: var(--medium-grey);
            border: 1px solid var(--border-grey);
            border-radius: 6px;
            color: var(--primary-white);
            font-size: 1rem;
            font-weight: 600;
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-grey);
        }

        .btn-wizard {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-prev {
            background: var(--medium-grey);
            color: var(--primary-white);
            border: 1px solid var(--border-grey);
        }

        .btn-prev:hover {
            background: var(--light-grey);
        }

        .btn-next {
            background: var(--accent-gold);
            color: var(--primary-black);
        }

        .btn-next:hover {
            background: #f0c14b;
            transform: translateY(-2px);
        }

        .btn-submit {
            background: var(--success);
            color: white;
        }

        .btn-submit:hover {
            background: #218838;
        }

        /* ===== UPLOAD DE IMÁGENES ===== */
        .upload-section {
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
        }

        .upload-group {
            background: var(--dark-grey);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-grey);
        }

        .upload-label {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-white);
            margin-bottom: 0.5rem;
        }

        .required-badge {
            background: var(--danger);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .optional-badge {
            background: var(--info);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .upload-description {
            color: var(--text-grey);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .image-upload-container input[type="file"] {
            display: none;
        }

        .upload-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: var(--medium-grey);
            border: 2px dashed var(--border-grey);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .upload-box:hover {
            border-color: var(--accent-gold);
            background: var(--light-grey);
        }

        .upload-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            filter: grayscale(100%);
            transition: filter 0.3s ease;
        }

        .upload-box:hover .upload-icon {
            filter: grayscale(0%);
        }

        .upload-text {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .upload-text strong {
            color: var(--primary-white);
            font-size: 1.1rem;
        }

        .upload-text span {
            color: var(--text-grey);
            font-size: 0.9rem;
        }

        .image-preview {
            margin-top: 1.5rem;
            display: none;
        }

        .image-preview.active {
            display: block;
        }

        .preview-container {
            position: relative;
            display: inline-block;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--accent-gold);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .preview-image {
            max-width: 100%;
            max-height: 400px;
            display: block;
        }

        .preview-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 1rem;
            color: white;
            font-size: 0.9rem;
        }

        .gallery-preview {
            margin-top: 1.5rem;
            display: none;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .gallery-preview.active {
            display: grid;
        }

        .gallery-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--border-grey);
            transition: all 0.3s ease;
        }

        .gallery-item:hover {
            border-color: var(--accent-gold);
            transform: scale(1.05);
        }

        .gallery-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        .gallery-item-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            padding: 0.5rem;
            font-size: 0.75rem;
            color: white;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="main-header">
    <div class="header-content">
        <a href="../index.php" class="logo-link">
            <img src="../imagenes/Element.jpg" alt="Logo" class="logo">
        </a>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="productos.php" class="nav-link active">Productos</a>
            <a href="pedidos.php" class="nav-link">Pedidos</a>
            <a href="usuarios.php" class="nav-link">Usuarios</a>
        </nav>

        <div class="nav-icons">
            <div class="dropdown">
                <a href="#" class="icon-link">
                    <img src="../imagenes/profile.png" alt="Admin">
                </a>
                <div class="dropdown-content">
                    <a href="../index.php">Ver tienda</a>
                    <a href="../logout.php">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- CONTENIDO -->
<main class="admin-main">
    
    <div class="page-header">
        <h1>➕ Crear Nuevo Producto</h1>
        <a href="productos.php" class="btn btn-secondary">← Volver</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            ✗ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="wizard-container">
        
        <!-- WIZARD STEPS -->
        <div class="wizard-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Info Básica</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Colores</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Tallas</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-label">Stock</div>
            </div>
            <div class="step" data-step="5">
                <div class="step-number">5</div>
                <div class="step-label">Imágenes</div>
            </div>
        </div>

        <form method="POST" id="productForm" enctype="multipart/form-data">
            
            <!--  INFORMACIÓN BÁSICA  -->
            <div class="wizard-content active" data-content="1">
                <h2 class="section-title">📝 Información del Producto</h2>
                
                <div class="form-group">
                    <label for="nombre">Nombre del Producto *</label>
                    <input type="text" id="nombre" name="nombre" required 
                           placeholder="Ej: Chaqueta Denim Premium">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4" 
                              placeholder="Describe las características del producto..."></textarea>
                </div>

                <!--  SELECTOR DE CATEGORÍA/GÉNERO -->
                <div class="form-group">
                    <label for="categoria">Categoría / Género *</label>
                    <select id="categoria" name="categoria" required 
                            style="padding: 0.8rem; background: var(--dark-grey); border: 1px solid var(--border-grey); border-radius: 6px; color: var(--primary-white); font-size: 1rem; width: 100%;">
                        <option value="">-- Seleccionar --</option>
                        <option value="mujer"> Mujer</option>
                        <option value="hombre"> Hombre</option>
                        <option value="unisex"> Unisex</option>
                    </select>
                    <small style="color: var(--text-grey); font-size: 0.85rem; margin-top: 0.3rem; display: block;">
                        Esta categoría se usará en los filtros del catálogo
                    </small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" required 
                               step="0.01" min="0" 
                               placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado *</label>
                        <select id="estado" name="estado" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- PASO 2: SELECCIONAR COLORES -->
            <div class="wizard-content" data-content="2">
                <h2 class="section-title">🎨 Selecciona los Colores Disponibles</h2>
                
                <div class="color-grid" id="colorGrid">
                    <?php foreach ($coloresPredefinidos as $color): ?>
                        <div class="color-option">
                            <input type="checkbox" 
                                   id="color_<?php echo strtolower($color); ?>" 
                                   name="colores[]" 
                                   value="<?php echo $color; ?>">
                            <label for="color_<?php echo strtolower($color); ?>" class="color-label">
                                <?php echo $color; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="custom-color-input">
                    <input type="text" 
                           id="customColor" 
                           placeholder="Agregar color personalizado..."
                           style="flex: 1; padding: 0.7rem; background: var(--dark-grey); border: 1px solid var(--border-grey); border-radius: 6px; color: var(--primary-white);">
                    <button type="button" onclick="agregarColorPersonalizado()" class="btn btn-secondary">
                        Agregar
                    </button>
                </div>
            </div>

            <!-- PASO 3: SELECCIONAR TALLAS -->
            <div class="wizard-content" data-content="3">
                <h2 class="section-title">📏 Selecciona las Tallas Disponibles</h2>
                
                <div class="talla-grid">
                    <?php foreach ($tallasPredefinidas as $talla): ?>
                        <div class="talla-option">
                            <input type="checkbox" 
                                   id="talla_<?php echo $talla; ?>" 
                                   name="tallas[]" 
                                   value="<?php echo $talla; ?>">
                            <label for="talla_<?php echo $talla; ?>" class="talla-label">
                                <?php echo $talla; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PASO 4: DEFINIR STOCK -->
            <div class="wizard-content" data-content="4">
                <h2 class="section-title">📦 Define el Stock por Variante</h2>
                <p style="color: var(--text-grey); margin-bottom: 1.5rem;">
                    Ingresa la cantidad de stock para cada combinación de color y talla
                </p>
                
                <div class="stock-matrix" id="stockMatrix">
                    <!-- Se genera dinámicamente con JavaScript -->
                </div>
            </div>

            <!-- PASO 5: SUBIR IMÁGENES -->
            <div class="wizard-content" data-content="5">
                <h2 class="section-title">📸 Imágenes del Producto</h2>
                
                <div class="upload-section">
                    <div class="upload-group">
                        <label class="upload-label">
                            <span class="required-badge">OBLIGATORIO</span>
                            Imagen Principal
                        </label>
                        <p class="upload-description">Esta será la imagen destacada del producto</p>
                        
                        <div class="image-upload-container">
                            <input type="file" 
                                   id="imagen_principal" 
                                   name="imagen_principal" 
                                   accept="image/jpeg,image/jpg,image/png,image/webp"
                                   required
                                   onchange="previewImage(this, 'preview_principal')">
                            
                            <label for="imagen_principal" class="upload-box">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">
                                    <strong>Seleccionar imagen principal</strong>
                                    <span>JPG, PNG o WEBP (máx. 5MB)</span>
                                </div>
                            </label>
                            
                            <div id="preview_principal" class="image-preview"></div>
                        </div>
                    </div>

                    <div class="upload-group">
                        <label class="upload-label">
                            <span class="optional-badge">OPCIONAL</span>
                            Galería de Imágenes
                        </label>
                        <p class="upload-description">Sube hasta 5 imágenes adicionales del producto</p>
                        
                        <div class="image-upload-container">
                            <input type="file" 
                                   id="galeria" 
                                   name="galeria[]" 
                                   accept="image/jpeg,image/jpg,image/png,image/webp"
                                   multiple
                                   onchange="previewMultipleImages(this, 'preview_galeria')">
                            
                            <label for="galeria" class="upload-box">
                                <div class="upload-icon">🖼️</div>
                                <div class="upload-text">
                                    <strong>Seleccionar imágenes de galería</strong>
                                    <span>Puedes seleccionar múltiples imágenes</span>
                                </div>
                            </label>
                            
                            <div id="preview_galeria" class="gallery-preview"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTONES DE NAVEGACIÓN -->
            <div class="wizard-actions">
                <button type="button" class="btn-wizard btn-prev" onclick="prevStep()" style="display: none;">
                    ← Anterior
                </button>
                <div style="flex: 1;"></div>
                <button type="button" class="btn-wizard btn-next" onclick="nextStep()">
                    Siguiente →
                </button>
                <button type="submit" class="btn-wizard btn-submit" style="display: none;">
                    ✓ Crear Producto
                </button>
            </div>

        </form>

    </div>

</main>

<script>
let currentStep = 1;
const totalSteps = 5;

function updateStepIndicators() {
    document.querySelectorAll('.step').forEach(step => {
        const stepNum = parseInt(step.dataset.step);
        step.classList.remove('active', 'completed');
        
        if (stepNum === currentStep) {
            step.classList.add('active');
        } else if (stepNum < currentStep) {
            step.classList.add('completed');
        }
    });
}

function showStep(step) {
    document.querySelectorAll('.wizard-content').forEach(content => {
        content.classList.remove('active');
    });
    
    document.querySelector(`[data-content="${step}"]`).classList.add('active');
    
    const btnPrev = document.querySelector('.btn-prev');
    const btnNext = document.querySelector('.btn-next');
    const btnSubmit = document.querySelector('.btn-submit');
    
    btnPrev.style.display = step === 1 ? 'none' : 'block';
    btnNext.style.display = step === totalSteps ? 'none' : 'block';
    btnSubmit.style.display = step === totalSteps ? 'block' : 'none';
    
    updateStepIndicators();
}


function nextStep() {
    if (currentStep === 1) {
        const nombre = document.getElementById('nombre').value.trim();
        const precio = parseFloat(document.getElementById('precio').value);
        const categoria = document.getElementById('categoria').value; // ⭐ NUEVO
        
        if (!nombre) {
            alert('El nombre del producto es obligatorio');
            return;
        }
        if (precio <= 0 || isNaN(precio)) {
            alert('El precio debe ser mayor a 0');
            return;
        }
 
        if (!categoria) {
            alert('Debes seleccionar una categoría (Mujer, Hombre o Unisex)');
            return;
        }
    }
    
    if (currentStep === 2) {
        const coloresSeleccionados = document.querySelectorAll('input[name="colores[]"]:checked');
        if (coloresSeleccionados.length === 0) {
            alert('Debes seleccionar al menos un color');
            return;
        }
    }
    
    if (currentStep === 3) {
        const tallasSeleccionadas = document.querySelectorAll('input[name="tallas[]"]:checked');
        if (tallasSeleccionadas.length === 0) {
            alert('Debes seleccionar al menos una talla');
            return;
        }
        
        generarMatrizStock();
    }
    
    if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function agregarColorPersonalizado() {
    const input = document.getElementById('customColor');
    const color = input.value.trim();
    
    if (!color) return;
    
    const colorGrid = document.getElementById('colorGrid');
    const colorId = 'color_' + color.toLowerCase().replace(/\s+/g, '_');
    
    if (document.getElementById(colorId)) {
        alert('Este color ya existe');
        return;
    }
    
    const div = document.createElement('div');
    div.className = 'color-option';
    div.innerHTML = `
        <input type="checkbox" id="${colorId}" name="colores[]" value="${color}" checked>
        <label for="${colorId}" class="color-label">${color}</label>
    `;
    
    colorGrid.appendChild(div);
    input.value = '';
}

function generarMatrizStock() {
    const coloresSeleccionados = Array.from(document.querySelectorAll('input[name="colores[]"]:checked'))
        .map(cb => cb.value);
    const tallasSeleccionadas = Array.from(document.querySelectorAll('input[name="tallas[]"]:checked'))
        .map(cb => cb.value);
    
    const stockMatrix = document.getElementById('stockMatrix');
    stockMatrix.innerHTML = '';
    
    coloresSeleccionados.forEach(color => {
        const row = document.createElement('div');
        row.className = 'stock-row';
        
        let html = `<div class="stock-row-header">🎨 ${color}</div><div class="stock-inputs">`;
        
        tallasSeleccionadas.forEach(talla => {
            html += `
                <div class="stock-input-group">
                    <label>${talla}</label>
                    <input type="number" 
                           name="stock[${color}][${talla}]" 
                           value="10" 
                           min="0" 
                           required>
                </div>
            `;
        });
        
        html += '</div>';
        row.innerHTML = html;
        stockMatrix.appendChild(row);
    });
}

// ===== FUNCIONES DE PREVIEW DE IMÁGENES =====

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (file.size > 5 * 1024 * 1024) {
            alert('La imagen es muy grande. Máximo 5MB');
            input.value = '';
            preview.classList.remove('active');
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Formato no permitido. Use JPG, PNG o WEBP');
            input.value = '';
            preview.classList.remove('active');
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const container = document.createElement('div');
            container.className = 'preview-container';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'preview-image';
            
            const info = document.createElement('div');
            info.className = 'preview-info';
            info.innerHTML = `
                <strong>${file.name}</strong><br>
                ${(file.size / 1024).toFixed(2)} KB
            `;
            
            container.appendChild(img);
            container.appendChild(info);
            preview.appendChild(container);
            preview.classList.add('active');
        };
        
        reader.readAsDataURL(file);
    } else {
        preview.classList.remove('active');
    }
}

function previewMultipleImages(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        const maxFiles = 5;
        const files = Array.from(input.files).slice(0, maxFiles);
        
        if (input.files.length > maxFiles) {
            alert(`Solo puedes subir máximo ${maxFiles} imágenes a la galería`);
        }
        
        let validFiles = 0;
        
        files.forEach((file, index) => {
            if (file.size > 5 * 1024 * 1024) {
                alert(`${file.name} es muy grande (máx. 5MB)`);
                return;
            }
            
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert(`${file.name} tiene un formato no permitido`);
                return;
            }
            
            validFiles++;
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const item = document.createElement('div');
                item.className = 'gallery-item';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                
                const info = document.createElement('div');
                info.className = 'gallery-item-info';
                info.innerHTML = `
                    <strong>Imagen ${index + 1}</strong><br>
                    ${(file.size / 1024).toFixed(2)} KB
                `;
                
                item.appendChild(img);
                item.appendChild(info);
                preview.appendChild(item);
            };
            
            reader.readAsDataURL(file);
        });
        
        if (validFiles > 0) {
            preview.classList.add('active');
        }
    } else {
        preview.classList.remove('active');
    }
}

// Auto-ocultar alertas
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
</script>

</body>
</html>