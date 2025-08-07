<?php
echo "<h1>🔐 Creando AdminMiddleware</h1>";

// Cambiar al directorio raíz
chdir('..');

// Crear AdminMiddleware.php
$adminMiddlewareContent = '<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route(\'login\');
        }

        if (!Auth::user()->is_admin) {
            abort(403, \'Acceso no autorizado\');
        }

        return $next($request);
    }
}';

file_put_contents('app/Http/Middleware/AdminMiddleware.php', $adminMiddlewareContent);
echo "✅ AdminMiddleware.php creado<br>";

// Verificar
if (file_exists('app/Http/Middleware/AdminMiddleware.php')) {
    echo "✅ AdminMiddleware verificado<br>";
} else {
    echo "❌ Error creando AdminMiddleware<br>";
}

// Limpiar caché una vez más
echo "<h2>🧹 Limpiando caché final...</h2>";
$output = shell_exec('php artisan config:clear 2>&1');
echo "<pre>$output</pre>";

echo "<h2>🎉 ¡Todo listo!</h2>";
echo "<p>Ahora Laravel debería funcionar completamente.</p>";
echo "<p><strong>Prueba estos enlaces:</strong></p>";
echo "<ul>";
echo "<li><a href='public/test-laravel11.php' target='_blank'>Test Laravel 11</a></li>";
echo "<li><a href='public/login' target='_blank'>Login (con /public/)</a></li>";
echo "<li><a href='login' target='_blank'>Login (sin /public/)</a></li>";
echo "</ul>";
?>