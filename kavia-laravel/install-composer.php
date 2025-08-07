<?php
echo "<h1>🎼 Instalador de Composer</h1>";

// Cambiar al directorio correcto
chdir(__DIR__);
echo "<p>📂 Directorio: " . getcwd() . "</p>";

// Descargar Composer
echo "<h2>📥 Descargando Composer...</h2>";
$installer = file_get_contents('https://getcomposer.org/installer');

if ($installer === false) {
    echo "<p style='color:red'>❌ Error descargando el instalador de Composer</p>";
    exit;
}

file_put_contents('composer-setup.php', $installer);
echo "<p style='color:green'>✅ Instalador descargado</p>";

// Ejecutar instalador
echo "<h2>⚙️ Instalando Composer...</h2>";
ob_start();
include 'composer-setup.php';
$output = ob_get_clean();
echo "<pre>$output</pre>";

// Verificar que composer.phar existe
if (file_exists('composer.phar')) {
    echo "<p style='color:green'>✅ composer.phar creado correctamente</p>";
    
    // Instalar dependencias
    echo "<h2>📦 Instalando dependencias de Laravel...</h2>";
    $installOutput = shell_exec('php composer.phar install --no-dev --optimize-autoloader 2>&1');
    echo "<pre>$installOutput</pre>";
    
    // Verificar vendor/
    if (is_dir('vendor')) {
        echo "<p style='color:green'>✅ Directorio vendor/ creado correctamente</p>";
        
        // Configurar .env si no existe
        if (!file_exists('.env')) {
            if (file_exists('.env.production')) {
                copy('.env.production', '.env');
                echo "<p style='color:green'>✅ Archivo .env configurado desde .env.production</p>";
            }
        }
        
        // Generar clave de aplicación
        echo "<h2>🔑 Configurando Laravel...</h2>";
        $keyOutput = shell_exec('php artisan key:generate --force 2>&1');
        echo "<pre>$keyOutput</pre>";
        
        // Cache config
        $cacheOutput = shell_exec('php artisan config:cache 2>&1');
        echo "<pre>$cacheOutput</pre>";
        
        echo "<h2 style='color:green'>🎉 ¡INSTALACIÓN COMPLETA!</h2>";
        echo "<p><strong>Ahora puedes probar:</strong></p>";
        echo "<ul>";
        echo "<li><a href='public/simple.php' target='_blank'>Test simple</a></li>";
        echo "<li><a href='public/login' target='_blank'>Login Laravel</a></li>";
        echo "<li><a href='public/admin/dashboard' target='_blank'>Panel Admin</a></li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color:red'>❌ No se pudo crear el directorio vendor/</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ No se pudo crear composer.phar</p>";
}

// Limpiar archivos temporales
if (file_exists('composer-setup.php')) {
    unlink('composer-setup.php');
}

echo "<hr>";
echo "<p><small>Script ejecutado desde: " . __FILE__ . "</small></p>";
?>