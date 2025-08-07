<?php
echo "<h1>🔍 Test Laravel 11</h1>";

// 1. Cargar autoloader
try {
    require_once '../vendor/autoload.php';
    echo "✅ Autoloader cargado<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Cargar Laravel app
try {
    $app = require_once '../bootstrap/app.php';
    echo "✅ Laravel app creado<br>";
} catch (Exception $e) {
    echo "❌ Error creando app: " . $e->getMessage() . "<br>";
    exit;
}

// 3. Probar con el método correcto de Laravel 11
try {
    // En Laravel 11 se usa así
    $request = Illuminate\Http\Request::create('/');
    $response = $app->handleRequest($request);
    echo "✅ Request manejada con handleRequest<br>";
    echo "Status: " . $response->getStatusCode() . "<br>";
} catch (Exception $e) {
    echo "❌ Error con handleRequest: " . $e->getMessage() . "<br>";
    
    // Intentar método alternativo
    try {
        $kernel = $app->make('Illuminate\Contracts\Http\Kernel');
        echo "✅ Kernel obtenido correctamente<br>";
        
        $request = Illuminate\Http\Request::create('/test');
        $response = $kernel->handle($request);
        echo "✅ Request procesada<br>";
        echo "Status: " . $response->getStatusCode() . "<br>";
        
    } catch (Exception $e2) {
        echo "❌ Error alternativo: " . $e2->getMessage() . "<br>";
    }
}

// 4. Probar rutas específicas
echo "<h2>4. Probando Rutas:</h2>";
$routes = [
    '/' => 'Inicio',
    '/login' => 'Login',
    '/admin/dashboard' => 'Dashboard'
];

foreach ($routes as $path => $name) {
    try {
        $request = Illuminate\Http\Request::create($path, 'GET');
        
        if (method_exists($app, 'handleRequest')) {
            $response = $app->handleRequest($request);
        } else {
            $kernel = $app->make('Illuminate\Contracts\Http\Kernel');
            $response = $kernel->handle($request);
        }
        
        $status = $response->getStatusCode();
        $statusText = match($status) {
            200 => "✅ OK",
            302 => "🔄 Redirect",
            404 => "❌ Not Found",
            500 => "💥 Error 500",
            default => "⚠️ Status $status"
        };
        
        echo "$statusText $name ($path)<br>";
        
    } catch (Exception $e) {
        echo "❌ $name ($path): " . $e->getMessage() . "<br>";
    }
}

echo "<h2>✅ Test Laravel 11 Completo</h2>";
echo "<p><a href='login'>Probar Login Real</a></p>";
?>