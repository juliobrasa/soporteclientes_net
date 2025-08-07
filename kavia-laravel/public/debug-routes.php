<?php
echo "<h2>🛣️ Debug de Rutas Laravel</h2>";

try {
    require_once '../vendor/autoload.php';
    $app = require_once '../bootstrap/app.php';
    
    // Intentar crear request simple
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    echo "✅ Laravel inicializado correctamente<br><br>";
    
    // Mostrar información de configuración
    echo "<h3>📋 Configuración:</h3>";
    echo "APP_ENV: " . (config('app.env') ?? 'undefined') . "<br>";
    echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "<br>";
    echo "APP_URL: " . (config('app.url') ?? 'undefined') . "<br>";
    echo "DB_CONNECTION: " . (config('database.default') ?? 'undefined') . "<br>";
    
    // Intentar conectar a la base de datos
    echo "<br><h3>🗄️ Conexión Base de Datos:</h3>";
    try {
        DB::connection()->getPdo();
        echo "✅ Conexión a base de datos exitosa<br>";
        
        // Verificar tablas importantes
        $tables = ['users', 'hoteles', 'ai_providers'];
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                echo "✅ Tabla '$table': $count registros<br>";
            } catch (Exception $e) {
                echo "❌ Tabla '$table': " . $e->getMessage() . "<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error de base de datos: " . $e->getMessage() . "<br>";
    }
    
    // Verificar rutas específicas
    echo "<br><h3>🛣️ Rutas Disponibles:</h3>";
    $routes = [
        'login' => 'GET',
        'admin' => 'GET', 
        'admin/dashboard' => 'GET',
        'api/test' => 'GET'
    ];
    
    foreach ($routes as $path => $method) {
        try {
            $request = Illuminate\Http\Request::create('/' . $path, $method);
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            
            if ($status == 200) {
                echo "✅ /$path ($method): $status OK<br>";
            } elseif ($status == 302) {
                echo "🔄 /$path ($method): $status Redirect<br>";
            } else {
                echo "⚠️ /$path ($method): $status<br>";
            }
        } catch (Exception $e) {
            echo "❌ /$path ($method): " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>🔗 <a href='login'>Ir al Login</a>";
    
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";  
    echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
    
    if (strpos($e->getMessage(), 'No application encryption key') !== false) {
        echo "<br>🔑 <strong>SOLUCIÓN:</strong> Ejecuta: <code>php artisan key:generate</code>";
    }
    
    if (strpos($e->getMessage(), 'database') !== false) {
        echo "<br>🗄️ <strong>PROBLEMA:</strong> Error de base de datos - verifica configuración en .env";
    }
}
?>