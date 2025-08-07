<?php
echo "<h1>🚀 Script de Deployment Completo</h1>";

// 1. Verificar que estamos en el directorio correcto
$currentDir = getcwd();
echo "<h2>📂 Directorio actual: $currentDir</h2>";

// 2. Verificar composer
$composerInstalled = shell_exec('which composer 2>/dev/null');
if (empty($composerInstalled)) {
    echo "<p style='color:red;'>❌ Composer NO está instalado en el servidor</p>";
    echo "<p><strong>SOLUCIÓN:</strong> Instalar Composer primero</p>";
    echo "<pre>curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer</pre>";
} else {
    echo "<p style='color:green;'>✅ Composer instalado en: " . trim($composerInstalled) . "</p>";
    
    // 3. Instalar dependencias
    echo "<h2>📦 Instalando dependencias...</h2>";
    $output = shell_exec('composer install --no-dev --optimize-autoloader 2>&1');
    echo "<pre>$output</pre>";
}

// 4. Verificar .env
if (!file_exists('.env')) {
    if (file_exists('.env.production')) {
        copy('.env.production', '.env');
        echo "<p style='color:green;'>✅ Copiado .env.production a .env</p>";
    } elseif (file_exists('.env.server')) {
        copy('.env.server', '.env');
        echo "<p style='color:green;'>✅ Copiado .env.server a .env</p>";
    } else {
        echo "<p style='color:red;'>❌ No se encontró archivo .env ni .env.production</p>";
    }
} else {
    echo "<p style='color:green;'>✅ Archivo .env ya existe</p>";
}

// 5. Generar clave si es necesario
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'APP_KEY=') === false || strpos($envContent, 'APP_KEY=base64:') === false) {
        echo "<h2>🔑 Generando clave de aplicación...</h2>";
        $keyOutput = shell_exec('php artisan key:generate --force 2>&1');
        echo "<pre>$keyOutput</pre>";
    }
}

// 6. Limpiar y optimizar
echo "<h2>🧹 Limpiando y optimizando...</h2>";
$commands = [
    'php artisan config:clear',
    'php artisan cache:clear', 
    'php artisan route:clear',
    'php artisan view:clear',
    'php artisan config:cache',
    'php artisan route:cache'
];

foreach ($commands as $cmd) {
    echo "<p><strong>Ejecutando:</strong> $cmd</p>";
    $output = shell_exec("$cmd 2>&1");
    echo "<pre>$output</pre>";
}

// 7. Verificar permisos
echo "<h2>🔐 Verificando permisos...</h2>";
$dirs = ['storage', 'storage/logs', 'storage/framework', 'bootstrap/cache'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        $color = $writable ? 'green' : 'red';
        $status = $writable ? '✅' : '❌';
        echo "<p style='color:$color;'>$status $dir " . ($writable ? 'escribible' : 'NO escribible') . "</p>";
        
        if (!$writable) {
            echo "<p>Ejecuta: <code>chmod -R 775 $dir</code></p>";
        }
    }
}

echo "<h2>🎯 ¡Deployment completado!</h2>";
echo "<p><a href='simple.php'>Probar simple.php</a> | <a href='login'>Probar login</a></p>";
?>