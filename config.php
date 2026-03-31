<?php
// Usamos la IP directa para evitar el error de DNS (getaddrinfo)
define('DB_HOST', getenv('DB_HOST') ?: '46.101.4.153');
define('DB_NAME', getenv('DB_NAME') ?: 'butqlct4sops6f3gimib');
define('DB_USER', getenv('DB_USER') ?: 'unncd9ladcewvvjd');
define('DB_PASS', getenv('DB_PASS') ?: 'lvKNKZytzSLnGyWDgFRi');

// Rutas (Asegúrate de que BASE_URL sea vacío para Render)
define('BASE_URL', ''); 
define('ADMIN_URL', BASE_URL . '/admin');
define('IMAGES_URL', BASE_URL . '/imagenes');
?>