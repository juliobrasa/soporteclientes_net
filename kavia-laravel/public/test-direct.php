<?php
echo "<h1>🎯 Test Directo - Simulando index.php</h1>";

try {
    // Simular exactamente lo que hace index.php
    define('LARAVEL_START', microtime(true));

    // Auto-loader
    require_once '../vendor/autoload.php';
    echo "✅ Autoloader cargado<br>";

    // Bootstrap Laravel
    $app = require_once '../bootstrap/app.php';
    echo "✅ Bootstrap cargado<br>";

    // Crear el kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ Kernel creado<br>";

    // Crear request simple para test
    $request = Illuminate\Http\Request::capture();
    echo "✅ Request capturado<br>";

    // Procesar request
    $response = $kernel->handle($request);
    echo "✅ Response generado<br>";
    
    $status = $response->getStatusCode();
    echo "Status Code: $status<br>";
    
    // Mostrar respuesta
    echo "<h2>📋 Respuesta de Laravel:</h2>";
    echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow: auto;'>";
    
    $content = $response->getContent();
    if (strlen($content) > 2000) {
        echo htmlspecialchars(substr($content, 0, 2000)) . "... (truncado)";
    } else {
        echo htmlspecialchars($content);
    }
    
    echo "</div>";
    
    $kernel->terminate($request, $response);
    echo "✅ Kernel terminado correctamente<br>";
    
    echo "<h2>🎉 ¡Laravel funciona!</h2>";
    echo "<p>El contenido de arriba es lo que Laravel devuelve.</p>";
    
    echo "<h3>Prueba estas URLs directamente:</h3>";
    echo "<ul>";
    echo "<li><a href='../login' target='_blank'>Login (../login)</a></li>";
    echo "<li><a href='login' target='_blank'>Login (login)</a></li>";
    echo "<li><a href='../admin/dashboard' target='_blank'>Dashboard (../admin/dashboard)</a></li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error:</h2>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    
    // Si el error es de facade, mostrar ayuda específica
    if (strpos($e->getMessage(), 'facade root') !== false) {
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>🔧 Solución para Facade Root:</h3>";
        echo "<p>Este error indica que Laravel no puede inicializar completamente.</p>";
        echo "<p>Prueba acceder directamente a las URLs sin usar tests:</p>";
        echo "<ul>";
        echo "<li><a href='../login'>../login</a></li>";
        echo "<li><a href='login'>login</a></li>";
        echo "</ul>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><small>Test ejecutado desde: " . __FILE__ . "</small></p>";
?>