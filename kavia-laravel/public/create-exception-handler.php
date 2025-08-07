<?php
echo "<h1>⚠️ Creando Exception Handler</h1>";

// Cambiar al directorio raíz
chdir('..');

// Crear directorio app/Exceptions si no existe
if (!is_dir('app/Exceptions')) {
    mkdir('app/Exceptions', 0755, true);
    echo "✅ Directorio app/Exceptions creado<br>";
}

// Crear Handler.php
$handlerContent = '<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        \'current_password\',
        \'password\',
        \'password_confirmation\',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}';

file_put_contents('app/Exceptions/Handler.php', $handlerContent);
echo "✅ app/Exceptions/Handler.php creado<br>";

// Crear también Provider si no existe
if (!is_dir('app/Providers')) {
    mkdir('app/Providers', 0755, true);
    echo "✅ Directorio app/Providers creado<br>";
}

// Verificar si RouteServiceProvider existe, si no, crearlo
if (!file_exists('app/Providers/RouteServiceProvider.php')) {
    $routeProviderContent = '<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application\'s "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = \'/home\';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for(\'api\', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware(\'api\')
                ->prefix(\'api\')
                ->group(base_path(\'routes/api.php\'));

            Route::middleware(\'web\')
                ->group(base_path(\'routes/web.php\'));
        });
    }
}';

    file_put_contents('app/Providers/RouteServiceProvider.php', $routeProviderContent);
    echo "✅ RouteServiceProvider.php creado<br>";
}

// Limpiar caché
echo "<h2>🧹 Limpiando caché...</h2>";
$cacheCommands = [
    'php artisan config:clear 2>&1',
    'php artisan cache:clear 2>&1'
];

foreach ($cacheCommands as $cmd) {
    $output = shell_exec($cmd);
    echo "<pre>$output</pre>";
}

// Verificación final
echo "<h2>✅ Verificación:</h2>";
$files = [
    'app/Exceptions/Handler.php' => 'Exception Handler',
    'app/Http/Kernel.php' => 'HTTP Kernel',
    'app/Http/Middleware/AdminMiddleware.php' => 'Admin Middleware',
    'app/Providers/RouteServiceProvider.php' => 'Route Service Provider'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description<br>";
    } else {
        echo "❌ $description NO EXISTE<br>";
    }
}

echo "<h2>🎯 ¡Exception Handler creado!</h2>";
echo "<p>Ahora Laravel debería funcionar sin errores de clases faltantes.</p>";
echo "<ul>";
echo "<li><a href='public/test-laravel11.php'>Test Laravel 11 Final</a></li>";
echo "<li><a href='public/login'>Login Laravel</a></li>";
echo "</ul>";
?>