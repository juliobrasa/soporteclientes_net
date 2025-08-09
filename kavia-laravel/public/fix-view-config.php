<?php
echo "<h1>🔧 Arreglando configuración de View</h1>";

// Cambiar al directorio raíz
chdir('..');

echo "<h2>1. Creando configuración completa de vistas:</h2>";

// Crear config/view.php
$viewConfig = "<?php

return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),
];";

file_put_contents('config/view.php', $viewConfig);
echo "✅ config/view.php creado<br>";

// Crear config/session.php
$sessionConfig = "<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => true,
    'same_site' => 'lax',
];";

file_put_contents('config/session.php', $sessionConfig);
echo "✅ config/session.php creado<br>";

// Actualizar config/app.php con más providers
$appConfig = "<?php

return [
    'name' => env('APP_NAME', 'Laravel'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => [
        'driver' => 'file',
    ],
    'providers' => [
        Illuminate\\Auth\\AuthServiceProvider::class,
        Illuminate\\Broadcasting\\BroadcastServiceProvider::class,
        Illuminate\\Bus\\BusServiceProvider::class,
        Illuminate\\Cache\\CacheServiceProvider::class,
        Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider::class,
        Illuminate\\Cookie\\CookieServiceProvider::class,
        Illuminate\\Database\\DatabaseServiceProvider::class,
        Illuminate\\Encryption\\EncryptionServiceProvider::class,
        Illuminate\\Filesystem\\FilesystemServiceProvider::class,
        Illuminate\\Foundation\\Providers\\FoundationServiceProvider::class,
        Illuminate\\Hashing\\HashServiceProvider::class,
        Illuminate\\Mail\\MailServiceProvider::class,
        Illuminate\\Notifications\\NotificationServiceProvider::class,
        Illuminate\\Pagination\\PaginationServiceProvider::class,
        Illuminate\\Pipeline\\PipelineServiceProvider::class,
        Illuminate\\Queue\\QueueServiceProvider::class,
        Illuminate\\Redis\\RedisServiceProvider::class,
        Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider::class,
        Illuminate\\Session\\SessionServiceProvider::class,
        Illuminate\\Translation\\TranslationServiceProvider::class,
        Illuminate\\Validation\\ValidationServiceProvider::class,
        Illuminate\\View\\ViewServiceProvider::class,
        App\\Providers\\AppServiceProvider::class,
    ],
    'aliases' => [
        'App' => Illuminate\\Support\\Facades\\App::class,
        'Arr' => Illuminate\\Support\\Arr::class,
        'Artisan' => Illuminate\\Support\\Facades\\Artisan::class,
        'Auth' => Illuminate\\Support\\Facades\\Auth::class,
        'Blade' => Illuminate\\Support\\Facades\\Blade::class,
        'Cache' => Illuminate\\Support\\Facades\\Cache::class,
        'Config' => Illuminate\\Support\\Facades\\Config::class,
        'Cookie' => Illuminate\\Support\\Facades\\Cookie::class,
        'DB' => Illuminate\\Support\\Facades\\DB::class,
        'Hash' => Illuminate\\Support\\Facades\\Hash::class,
        'Log' => Illuminate\\Support\\Facades\\Log::class,
        'Route' => Illuminate\\Support\\Facades\\Route::class,
        'Session' => Illuminate\\Support\\Facades\\Session::class,
        'URL' => Illuminate\\Support\\Facades\\URL::class,
        'View' => Illuminate\\Support\\Facades\\View::class,
    ],
];";

file_put_contents('config/app.php', $appConfig);
echo "✅ config/app.php actualizado con ViewServiceProvider<br>";

echo "<h2>2. Verificando directorio de vistas:</h2>";
if (!is_dir('resources/views')) {
    mkdir('resources/views', 0755, true);
    echo "✅ Directorio resources/views creado<br>";
}

if (!is_dir('storage/framework/views')) {
    mkdir('storage/framework/views', 0755, true);
    echo "✅ Directorio storage/framework/views creado<br>";
}

echo "<h2>3. Limpiando caché:</h2>";
$commands = [
    'php artisan config:clear 2>&1',
    'php artisan view:clear 2>&1',
    'php artisan cache:clear 2>&1'
];

foreach ($commands as $cmd) {
    $output = shell_exec($cmd);
    echo "<pre>$output</pre>";
}

echo "<h2>✅ Configuración de vistas arreglada</h2>";
echo "<p>Ahora prueba:</p>";
echo "<ul>";
echo "<li><a href='public/show-error.php'>Verificar errores</a></li>";
echo "<li><a href='public/index.php'>Index Laravel</a></li>";
echo "<li><a href='login'>Login directo</a></li>";
echo "</ul>";
?>