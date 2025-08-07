<?php
echo "<h1>🛠️ Creando Kernel y Middleware Manualmente</h1>";

// Cambiar al directorio raíz
chdir('..');
$currentDir = getcwd();
echo "<p>📂 Directorio: $currentDir</p>";

// Crear directorio app/Http si no existe
if (!is_dir('app/Http')) {
    mkdir('app/Http', 0755, true);
    echo "✅ Directorio app/Http creado<br>";
}

// Crear app/Http/Kernel.php
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
echo "✅ app/Http/Kernel.php creado<br>";

// Crear directorio Middleware si no existe
if (!is_dir('app/Http/Middleware')) {
    mkdir('app/Http/Middleware', 0755, true);
    echo "✅ Directorio app/Http/Middleware creado<br>";
}

// Crear RedirectIfAuthenticated.php
$redirectContent = '<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect(\'/home\');
            }
        }

        return $next($request);
    }
}';

file_put_contents('app/Http/Middleware/RedirectIfAuthenticated.php', $redirectContent);
echo "✅ RedirectIfAuthenticated.php creado<br>";

// Limpiar caché
echo "<h2>🧹 Limpiando caché...</h2>";
$cacheCommands = [
    'php artisan config:clear 2>&1',
    'php artisan cache:clear 2>&1',
    'php artisan route:clear 2>&1'
];

foreach ($cacheCommands as $cmd) {
    $output = shell_exec($cmd);
    echo "<p><strong>Ejecutando:</strong> " . str_replace(' 2>&1', '', $cmd) . "</p>";
    echo "<pre>$output</pre>";
}

// Verificar archivos creados
echo "<h2>✅ Verificación:</h2>";
$files = [
    'app/Http/Kernel.php' => 'HTTP Kernel',
    'app/Http/Middleware/RedirectIfAuthenticated.php' => 'Middleware RedirectIfAuthenticated',
    'app/Http/Middleware/AdminMiddleware.php' => 'Middleware Admin',
    'vendor/autoload.php' => 'Autoloader',
    '.env' => 'Configuración'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description ($file)<br>";
    } else {
        echo "❌ $description ($file) NO EXISTE<br>";
    }
}

echo "<h2>🎯 ¡Archivos creados!</h2>";
echo "<p>Ahora puedes probar:</p>";
echo "<ul>";
echo "<li><a href='public/test-laravel11.php'>Test Laravel 11</a></li>";
echo "<li><a href='public/login'>Login</a></li>";
echo "<li><a href='login'>Login (sin /public/)</a></li>";
echo "</ul>";
?>