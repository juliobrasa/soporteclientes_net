<?php
echo "<h1>🔍 Debug Laravel Avanzado</h1>";

// 1. Intentar cargar autoloader
echo "<h2>1. Cargando Autoloader:</h2>";
try {
    require_once '../vendor/autoload.php';
    echo "✅ Autoloader cargado<br>";
} catch (Exception $e) {
    echo "❌ Error cargando autoloader: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Verificar .env
echo "<h2>2. Verificando .env:</h2>";
if (file_exists('../.env')) {
    $env = file_get_contents('../.env');
    if (strpos($env, 'APP_KEY=') !== false) {
        echo "✅ APP_KEY encontrada<br>";
    } else {
        echo "❌ APP_KEY no encontrada<br>";
    }
} else {
    echo "❌ .env no existe<br>";
}

// 3. Intentar cargar Laravel
echo "<h2>3. Inicializando Laravel:</h2>";
try {
    $app = require_once '../bootstrap/app.php';
    echo "✅ Bootstrap cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en bootstrap: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "<br>";
    exit;
}

// 4. Intentar crear kernel
echo "<h2>4. Creando HTTP Kernel:</h2>";
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ HTTP Kernel creado<br>";
} catch (Exception $e) {
    echo "❌ Error creando kernel: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "<br>";
    exit;
}

// 5. Probar una request simple
echo "<h2>5. Probando Request:</h2>";
try {
    $request = Illuminate\Http\Request::create('/');
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    echo "✅ Request procesada - Status: $statusCode<br>";
} catch (Exception $e) {
    echo "❌ Error en request: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "<br>";
}

// 6. Verificar permisos
echo "<h2>6. Verificando Permisos:</h2>";
$dirs = ['../storage', '../storage/logs', '../bootstrap/cache'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        echo ($writable ? "✅" : "❌") . " $dir " . ($writable ? "escribible" : "NO escribible") . "<br>";
    } else {
        echo "❌ $dir no existe<br>";
    }
}

// 7. Verificar logs
echo "<h2>7. Últimos Errores en Logs:</h2>";
$logFile = '../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $errorLines = array_filter($lines, function($line) {
        return strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false;
    });
    
    $lastErrors = array_slice($errorLines, -5, 5);
    
    if (!empty($lastErrors)) {
        echo "<pre style='background:#ffe6e6; padding:10px; max-height:200px; overflow:auto;'>";
        foreach ($lastErrors as $error) {
            echo htmlspecialchars($error) . "\n";
        }
        echo "</pre>";
    } else {
        echo "✅ No hay errores recientes en los logs<br>";
    }
} else {
    echo "❌ Log file no encontrado<br>";
}

echo "<hr>";
echo "<p>Debug completado. Si ves ✅ en todos los pasos, Laravel debería funcionar.</p>";
?>