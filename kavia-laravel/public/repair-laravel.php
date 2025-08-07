<?php
echo "<h1>🔧 Reparación Completa de Laravel</h1>";

// Cambiar al directorio raíz
chdir('..');
$rootDir = getcwd();
echo "<p>📂 Directorio: $rootDir</p>";

// 1. Verificar y regenerar APP_KEY
echo "<h2>🔑 1. Verificando APP_KEY:</h2>";
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    
    if (strpos($envContent, 'APP_KEY=base64:') === false) {
        echo "❌ APP_KEY inválida o faltante<br>";
        
        // Generar nueva clave manualmente
        $key = base64_encode(random_bytes(32));
        $envContent = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=base64:$key", $envContent);
        
        if (strpos($envContent, 'APP_KEY=') === false) {
            $envContent .= "\nAPP_KEY=base64:$key\n";
        }
        
        file_put_contents('.env', $envContent);
        echo "✅ Nueva APP_KEY generada<br>";
    } else {
        echo "✅ APP_KEY válida encontrada<br>";
    }
} else {
    echo "❌ Archivo .env no existe<br>";
    
    if (file_exists('.env.production')) {
        copy('.env.production', '.env');
        echo "✅ Copiado .env.production a .env<br>";
    } else {
        echo "❌ Tampoco existe .env.production<br>";
    }
}

// 2. Crear configuración básica si no existe
echo "<h2>⚙️ 2. Verificando configuración:</h2>";
if (!file_exists('config/app.php')) {
    echo "❌ config/app.php no existe<br>";
    
    // Crear config básico
    if (!is_dir('config')) {
        mkdir('config', 0755, true);
    }
    
    $basicConfig = "<?php
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
    
    file_put_contents('config/app.php', $basicConfig);
    echo "✅ config/app.php básico creado<br>";
} else {
    echo "✅ config/app.php existe<br>";
}

// 3. Verificar permisos críticos
echo "<h2>🔐 3. Verificando permisos:</h2>";
$criticalDirs = [
    'storage' => 0775,
    'storage/app' => 0775,
    'storage/framework' => 0775,
    'storage/framework/cache' => 0775,
    'storage/framework/sessions' => 0775,
    'storage/framework/views' => 0775,
    'storage/logs' => 0775,
    'bootstrap/cache' => 0775
];

foreach ($criticalDirs as $dir => $perm) {
    if (!is_dir($dir)) {
        mkdir($dir, $perm, true);
        echo "✅ Directorio $dir creado<br>";
    }
    
    if (!is_writable($dir)) {
        chmod($dir, $perm);
        echo "✅ Permisos de $dir corregidos<br>";
    } else {
        echo "✅ $dir escribible<br>";
    }
}

// 4. Limpieza total
echo "<h2>🧹 4. Limpieza total:</h2>";
$cleanCommands = [
    'rm -rf bootstrap/cache/*.php',
    'rm -rf storage/framework/cache/data/*',
    'rm -rf storage/framework/sessions/*',
    'rm -rf storage/framework/views/*'
];

foreach ($cleanCommands as $cmd) {
    shell_exec($cmd);
    echo "✅ Ejecutado: $cmd<br>";
}

echo "<h2>🎯 Reparación completada</h2>";
echo "<p><strong>Ahora prueba directamente (sin tests):</strong></p>";
echo "<ul>";
echo "<li><a href='../login' target='_blank'>../login</a></li>";
echo "<li><a href='login' target='_blank'>login</a></li>";
echo "<li><a href='../public/index.php' target='_blank'>../public/index.php</a></li>";
echo "</ul>";

echo "<p><em>Si sigue fallando, el problema puede ser más profundo en la configuración del servidor.</em></p>";
?>