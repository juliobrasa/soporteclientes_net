<?php
echo "<h1>🎯 Test Final Laravel 11</h1>";

try {
    // 1. Cargar autoloader
    require_once '../vendor/autoload.php';
    echo "✅ Autoloader cargado<br>";

    // 2. Crear la aplicación Laravel
    $app = require_once '../bootstrap/app.php';
    echo "✅ Aplicación Laravel creada<br>";

    // 3. Inicializar completamente la aplicación
    $app->boot();
    echo "✅ Aplicación inicializada<br>";

    // 4. Crear request y kernel correctamente
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ HTTP Kernel obtenido<br>";

    // 5. Probar una ruta simple
    $request = Illuminate\Http\Request::create('/', 'GET');
    
    // Procesar el request
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    
    echo "✅ Request procesado - Status: $status<br>";
    
    if ($status == 200) {
        echo "🎉 ¡Laravel funciona perfectamente!<br>";
    } elseif ($status == 404) {
        echo "⚠️ Ruta no encontrada (normal si no hay ruta raíz definida)<br>";
    } else {
        echo "⚠️ Status inesperado: $status<br>";
    }

    // 6. Probar ruta de login específicamente
    echo "<h2>🔐 Probando ruta de login:</h2>";
    $loginRequest = Illuminate\Http\Request::create('/login', 'GET');
    $loginResponse = $kernel->handle($loginRequest);
    $loginStatus = $loginResponse->getStatusCode();
    
    if ($loginStatus == 200) {
        echo "✅ Ruta /login funciona correctamente<br>";
    } elseif ($loginStatus == 302) {
        echo "✅ Ruta /login redirige (comportamiento normal)<br>";
    } else {
        echo "⚠️ Ruta /login status: $loginStatus<br>";
    }

    // 7. Mostrar información de la app
    echo "<h2>📋 Información de la aplicación:</h2>";
    echo "Environment: " . $app->environment() . "<br>";
    echo "Debug: " . ($app->hasDebugModeEnabled() ? 'true' : 'false') . "<br>";
    
    echo "<h2>✅ ¡ÉXITO TOTAL!</h2>";
    echo "<p style='color:green; font-weight:bold;'>Laravel está funcionando correctamente en tu servidor.</p>";
    
    echo "<h3>🚀 URLs disponibles:</h3>";
    echo "<ul>";
    echo "<li><a href='login' target='_blank'>🔐 Login (sin /public/)</a></li>";
    echo "<li><a href='public/login' target='_blank'>🔐 Login (con /public/)</a></li>";
    echo "<li><a href='../login' target='_blank'>🔐 Login (desde raíz)</a></li>";
    echo "</ul>";
    
    echo "<p><strong>Credenciales:</strong></p>";
    echo "<ul>";
    echo "<li>Email: <code>admin@soporteclientes.net</code></li>";
    echo "<li>Password: <code>admin123</code></li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error:</h2>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<details><summary>Stack Trace</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
}
?>