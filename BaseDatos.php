<?php

class BaseDatos {
    private $pdo;

public function __construct() {
    $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Forzamos un tiempo de espera de 10 segundos
        PDO::ATTR_TIMEOUT            => 10, 
    ];

    try {
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die('Error de conexión: ' . $e->getMessage());
    }
}

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }


        // ===== NUEVO MÉTODO AGREGADO =====
        public function getLastInsertId() {
            return $this->pdo->lastInsertId();
        }

        public function lastInsertId() {
            return $this->pdo->lastInsertId();
        }

        public function beginTransaction() {
            return $this->pdo->beginTransaction();
        }

        public function commit() {
            return $this->pdo->commit();
        }

        public function rollback() {
            if ($this->pdo->inTransaction()) {
                return $this->pdo->rollBack();
            }
        }
        // =================================

        public function buscarUsuarioPorCorreo($correo) {
            $sql = "SELECT * FROM usuarios WHERE correo = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$correo]);
            return $stmt->fetch();
        }

        public function registrarUsuario($nombre, $apellido, $correo, $passwordHash) {
            // 1. Crear usuario
            $sql = "INSERT INTO usuarios (nombre, apellido, correo, contrasena_hash)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nombre, $apellido, $correo, $passwordHash]);

            $idUsuario = $this->pdo->lastInsertId();

            // 2. Asignar rol por defecto (USUARIO, no admin)
            $sqlRol = "INSERT INTO usuario_rol (id_usuario, id_rol)
                    VALUES (?, 2)";
            $stmtRol = $this->pdo->prepare($sqlRol);
            $stmtRol->execute([$idUsuario]);

            return $idUsuario;
        }


    public function login($correo, $password) {
        $usuario = $this->buscarUsuarioPorCorreo($correo);

        if (!$usuario) {
            return false;
        }

        if (!password_verify($password, $usuario['contrasena_hash'])) {
            return false;
        }

        return $usuario;
    }

    public function crearCodigoVerificacion($correo) {
        $codigo = random_int(100000, 999999);
        $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Invalidar códigos anteriores
        $sql = "UPDATE codigos_verificacion 
                SET usado = 1 
                WHERE correo = ?";
        $this->query($sql, [$correo]);

        // Insertar nuevo código
        $sql = "INSERT INTO codigos_verificacion (correo, codigo, fecha_expiracion)
                VALUES (?, ?, ?)";
        $this->query($sql, [$correo, $codigo, $expira]);

        return $codigo;
    }

        public function crearUsuarioGoogle($nombre, $correo, $firebase_uid) {
            $sql = "INSERT INTO usuarios (nombre, correo, firebase_uid, rol)
                    VALUES (?, ?, ?, 'usuario')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nombre, $correo, $firebase_uid]);
            return $this->pdo->lastInsertId();
        }

    public function obtenerCarritoActivo($idUsuario) {
        $sql = "
            SELECT id_carrito
            FROM carrito
            WHERE id_usuario = ? AND estado = 'activo'
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idUsuario]);
        return $stmt->fetchColumn();
    }

    public function crearCarrito($idUsuario) {
        $sql = "
            INSERT INTO carrito (id_usuario, estado, fecha_creacion)
            VALUES (?, 'activo', NOW())
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idUsuario]);
        return $this->pdo->lastInsertId();
    }

    public function obtenerItemsCarrito($idUsuario)
    {
        $sql = "
            SELECT 
                ic.id_item,
                ic.cantidad,
                ic.precio_unitario,
                p.nombre AS nombre_producto,
                CONCAT(pv.color, ' / ', pv.talla) AS variante
            FROM carrito c
            INNER JOIN item_carrito ic ON ic.id_carrito = c.id_carrito
            INNER JOIN productos_variante pv ON pv.id_variante = ic.id_variante
            INNER JOIN productos p ON p.id_producto = pv.id_producto
            WHERE c.id_usuario = ?
            AND c.estado = 'activo'
        ";

        return $this->query($sql, [$idUsuario]);
    }

    public function obtenerOCrearCarrito($idUsuario) {
        $idCarrito = $this->obtenerCarritoActivo($idUsuario);

        if (!$idCarrito) {
            $idCarrito = $this->crearCarrito($idUsuario);
        }

        return $idCarrito;
    }

    public function agregarItemCarrito($idUsuario, $idVariante, $cantidad) {

        $idCarrito = $this->obtenerOCrearCarrito($idUsuario);

        // Precio actual del producto
        $sqlPrecio = "
            SELECT p.precio
            FROM productos p
            JOIN productos_variante v ON p.id_producto = v.id_producto
            WHERE v.id_variante = ?
            LIMIT 1
        ";
        $stmtPrecio = $this->pdo->prepare($sqlPrecio);
        $stmtPrecio->execute([$idVariante]);
        $precio = $stmtPrecio->fetchColumn();

        if (!$precio) {
            throw new Exception("Producto no válido");
        }

        // ¿Ya existe en el carrito?
        $sqlExiste = "
            SELECT id_item, cantidad
            FROM item_carrito
            WHERE id_carrito = ? AND id_variante = ?
            LIMIT 1
        ";
        $stmtExiste = $this->pdo->prepare($sqlExiste);
        $stmtExiste->execute([$idCarrito, $idVariante]);
        $item = $stmtExiste->fetch();

        if ($item) {
            // Sumar cantidad
            $sqlUpdate = "
                UPDATE item_carrito
                SET cantidad = cantidad + ?
                WHERE id_item = ?
            ";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$cantidad, $item['id_item']]);
        } else {
            // Insertar nuevo
            $sqlInsert = "
                INSERT INTO item_carrito
                (id_carrito, id_variante, cantidad, precio_unitario)
                VALUES (?, ?, ?, ?)
            ";
            $stmtInsert = $this->pdo->prepare($sqlInsert);
            $stmtInsert->execute([$idCarrito, $idVariante, $cantidad, $precio]);
        }

        return true;
    }
    public function eliminarItemCarrito($idUsuario, $idItem) {

        $sql = "
            DELETE ic
            FROM item_carrito ic
            JOIN carrito c ON ic.id_carrito = c.id_carrito
            WHERE ic.id_item = ? AND c.id_usuario = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$idItem, $idUsuario]);
    }


    public function obtenerProductos() {
        return $this->query("SELECT * FROM productos")->fetchAll();
    }

    public function eliminarProducto($id) {
        $this->query("DELETE FROM productos WHERE id_producto = ?", [$id]);
    }

    
}