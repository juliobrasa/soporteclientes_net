<?php
echo "<h2>🧪 Diagnóstico Laravel Específico</h2>";

try {
    // Intentar cargar Laravel
    echo "1. Intentando cargar Laravel...<br>";
    
    // Verificar si existe el autoloader
    if (file_exists('../vendor/autoload.php')) {
        echo "✅ Autoloader encontrado<br>";
        require_once '../vendor/autoload.php';
        echo "✅ Autoloader cargado exitosamente<br>";
    } else {
        echo "❌ Autoloader NO encontrado en ../vendor/autoload.php<br>";
        die("Error: Composer no está instalado o vendor/ no existe");
    }
    
    // Verificar bootstrap
    if (file_exists('../bootstrap/app.php')) {
        echo "✅ Bootstrap encontrado<br>";
        $app = require_once '../bootstrap/app.php';
        echo "✅ Bootstrap cargado exitosamente<br>";
    } else {
        echo "❌ Bootstrap NO encontrado<br>";
        die("Error: bootstrap/app.php no existe");
    }
    
    // Verificar .env
    if (file_exists('../.env')) {
        echo "✅ Archivo .env encontrado<br>";
        $env = file_get_contents('../.env');
        if (strpos($env, 'APP_KEY') !== false) {
            echo "✅ APP_KEY encontrada en .env<br>";
        } else {
            echo "❌ APP_KEY NO encontrada en .env<br>";
        }
    } else {
        echo "❌ Archivo .env NO encontrado<br>";
    }
    
    // Verificar storage
    if (is_writable('../storage/logs')) {
        echo "✅ storage/logs escribible<br>";
    } else {
        echo "❌ storage/logs NO escribible<br>";
    }
    
    // Intentar crear instancia básica
    echo "<br>2. Intentando inicializar Laravel...<br>";
    
    // Verificar si podemos acceder a la configuración básica
    if (isset($app)) {
        echo "✅ App instance creada<br>";
        
        // Intentar obtener kernel
        try {
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            echo "✅ HTTP Kernel creado<br>";
        } catch (Exception $e) {
            echo "❌ Error creando HTTP Kernel: " . $e->getMessage() . "<br>";
        }
    }
    
    // Verificar último error en logs
    echo "<br>3. Verificando logs de Laravel...<br>";
    $logFile = '../storage/logs/laravel.log';
    if (file_exists($logFile)) {
        echo "✅ Log file encontrado<br>";
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        $lastLines = array_slice($lines, -20, 20);
        
        echo "<details><summary>📋 Últimas 20 líneas del log</summary>";
        echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px;'>";
        foreach ($lastLines as $line) {
            if (strpos($line, 'ERROR') !== false) {
                echo "<span style='color: red;'>" . htmlspecialchars($line) . "</span>\n";
            } elseif (strpos($line, 'Exception') !== false) {
                echo "<span style='color: orange;'>" . htmlspecialchars($line) . "</span>\n";
            } else {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo "</pre></details>";
    } else {
        echo "❌ Log file NO encontrado<br>";
    }
    
    echo "<br>✅ Diagnóstico completado sin errores fatales<br>";
    echo "<br>🔗 <a href='login'>Probar Login Laravel</a> | ";
    echo "<a href='test.php'>Volver al test básico</a>";
    
} catch (Exception $e) {
    echo "<br>❌ <strong>ERROR FATAL:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    echo "<details><summary>Stack Trace</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
} catch (Error $e) {
    echo "<br>❌ <strong>PHP ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
}
?>