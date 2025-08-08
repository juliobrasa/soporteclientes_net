<?php
/**
 * Redireccionador directo para login de clientes
 */

// Configurar el directorio base de Laravel
$laravelPath = __DIR__ . '/kavia-laravel';

// Verificar que Laravel existe
if (!is_dir($laravelPath)) {
    die('Error: Laravel no encontrado');
}

// Simular la petición para Laravel
$_SERVER['REQUEST_URI'] = '/client/login';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Cambiar al directorio de Laravel
chdir($laravelPath);

// Configurar el path correcto
require_once $laravelPath . '/vendor/autoload.php';

// Bootstrap de Laravel
$app = require_once $laravelPath . '/bootstrap/app.php';

// Manejar la petición
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Forzar la ruta correcta
$request->server->set('REQUEST_URI', '/client/login');

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
?>