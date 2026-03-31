<?php
// Prioridad a las variables de entorno de Render
define('DB_HOST', getenv('DB_HOST') ?: '46.101.4.153');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'butqlct4sops6f3gimib');
define('DB_USER', getenv('DB_USER') ?: 'unncd9ladcewvvjd');
define('DB_PASS', getenv('DB_PASS') ?: 'lvKNKZytzSLnGyWDgFRi');

define('BASE_URL', ''); 
define('ADMIN_URL', '/admin');
define('IMAGES_URL', '/imagenes');
?>