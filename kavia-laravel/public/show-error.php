<?php
// Habilitar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚨 Mostrar Error Exacto</h1>";

try {
    // Intentar cargar Laravel paso a paso
    echo "1. Cargando autoloader...<br>";
    require_once '../vendor/autoload.php';
    echo "✅ Autoloader OK<br>";

    echo "2. Cargando bootstrap...<br>";
    $app = require_once '../bootstrap/app.php';
    echo "✅ Bootstrap OK<br>";

    echo "3. Verificando archivos críticos...<br>";
    $criticalFiles = [
        '../config/app.php' => 'Config App',
        '../app/Http/Kernel.php' => 'HTTP Kernel',
        '../app/Exceptions/Handler.php' => 'Exception Handler',
        '../.env' => 'Environment'
    ];
    
    foreach ($criticalFiles as $file => $desc) {
        if (file_exists($file)) {
            echo "✅ $desc existe<br>";
        } else {
            echo "❌ $desc NO EXISTE: $file<br>";
        }
    }

    echo "4. Intentando crear kernel...<br>";
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ Kernel creado<br>";

    echo "5. Capturando request...<br>";
    $request = Illuminate\Http\Request::capture();
    echo "✅ Request capturado<br>";

    echo "6. Procesando request...<br>";
    $response = $kernel->handle($request);
    echo "✅ Response generado con status: " . $response->getStatusCode() . "<br>";

    echo "<h2>🎉 ¡Laravel funciona!</h2>";

} catch (Error $e) {
    echo "<h2 style='color:red'>❌ PHP Error:</h2>";
    echo "<strong>Mensaje:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Exception:</h2>";
    echo "<strong>Mensaje:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// También revisar los logs de error de PHP si existen
echo "<h2>📋 Info del sistema:</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";

echo "<hr>";
echo "<p><strong>Si ves este mensaje, PHP funciona. El problema está en Laravel.</strong></p>";
?>