<?php
/**
 * Laravel Bootstrap para Panel de Clientes
 * 
 * Este archivo redirige todas las peticiones del panel de clientes
 * al sistema Laravel ubicado en kavia-laravel/
 */

// Configurar el directorio base de Laravel
$laravelPath = __DIR__ . '/kavia-laravel';

// Verificar que Laravel existe
if (!is_dir($laravelPath)) {
    die('Error: Laravel no encontrado en ' . $laravelPath);
}

// Configurar variables de entorno para Laravel
$_SERVER['DOCUMENT_ROOT'] = $laravelPath . '/public';
$_SERVER['SCRIPT_FILENAME'] = $laravelPath . '/public/index.php';

// Cambiar al directorio de Laravel
chdir($laravelPath);

// Incluir el autoloader de Laravel
require_once $laravelPath . '/vendor/autoload.php';

// Bootear la aplicación Laravel
$app = require_once $laravelPath . '/bootstrap/app.php';

// Crear y manejar la petición
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);
?>