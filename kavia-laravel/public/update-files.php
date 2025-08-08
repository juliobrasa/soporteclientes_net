<?php
echo "<h1>🔄 Actualizando Archivos desde Git</h1>";

// Cambiar al directorio raíz del proyecto
chdir('..');
$currentDir = getcwd();
echo "<p>📂 Directorio: $currentDir</p>";

// Verificar si es un repositorio git
if (is_dir('.git')) {
    echo "✅ Repositorio Git encontrado<br>";
    
    // Hacer git pull
    echo "<h2>📥 Descargando últimos cambios...</h2>";
    $pullOutput = shell_exec('git pull origin master 2>&1');
    echo "<pre>$pullOutput</pre>";
    
    // Verificar que el archivo Kernel existe ahora
    if (file_exists('app/Http/Kernel.php')) {
        echo "✅ app/Http/Kernel.php ahora existe<br>";
    } else {
        echo "❌ app/Http/Kernel.php aún no existe<br>";
        
        // Crear manualmente si git pull falló
        echo "<h2>🛠️ Creando Kernel manualmente...</h2>";
        
        if (!is_dir('app/Http')) {
            mkdir('app/Http', 0755, true);
        }
        
        $kernelContent = '<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        \'web\' => [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        \'api\' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.\':api\',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        \'auth\' => \Illuminate\Auth\Middleware\Authenticate::class,
        \'guest\' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        \'admin\' => \App\Http\Middleware\AdminMiddleware::class,
    ];
}';
        
        file_put_contents('app/Http/Kernel.php', $kernelContent);
        echo "✅ Kernel creado manualmente<br>";
    }
    
    // Limpiar caché de Laravel
    echo "<h2>🧹 Limpiando caché...</h2>";
    $cacheCommands = [
        'php artisan config:clear',
        'php artisan cache:clear',
        'php artisan route:clear',
        'php artisan view:clear'
    ];
    
    foreach ($cacheCommands as $cmd) {
        $output = shell_exec("$cmd 2>&1");
        echo "<p><strong>$cmd:</strong></p><pre>$output</pre>";
    }
    
    // Verificar que todo funciona
    echo "<h2>🧪 Verificación Final:</h2>";
    if (file_exists('app/Http/Kernel.php')) {
        echo "✅ Kernel existe<br>";
        echo "✅ Archivos actualizados correctamente<br>";
        echo "<p><a href='public/test-laravel11.php'>Probar Laravel 11</a></p>";
        echo "<p><a href='public/login'>Probar Login</a></p>";
    } else {
        echo "❌ Kernel aún no existe<br>";
    }
    
} else {
    echo "❌ No es un repositorio Git<br>";
    echo "❌ No se puede hacer git pull<br>";
}

echo "<hr>";
echo "<p><small>Script ejecutado desde: " . __FILE__ . "</small></p>";
?>