<?php
echo "<h1>🎼 Instalador de Composer (Versión Corregida)</h1>";

// Configurar variables de entorno
putenv('HOME=' . __DIR__);
putenv('COMPOSER_HOME=' . __DIR__ . '/.composer');

// Cambiar al directorio correcto
chdir(__DIR__);
echo "<p>📂 Directorio: " . getcwd() . "</p>";

// Crear directorio .composer si no existe
if (!is_dir('.composer')) {
    mkdir('.composer', 0755, true);
    echo "<p>📁 Directorio .composer creado</p>";
}

// Descargar Composer directamente como .phar
echo "<h2>📥 Descargando Composer...</h2>";

$composerUrl = 'https://getcomposer.org/download/latest-stable/composer.phar';
$composerPhar = file_get_contents($composerUrl);

if ($composerPhar === false) {
    echo "<p style='color:red'>❌ Error descargando Composer directamente</p>";
    echo "<p>Intentando método alternativo...</p>";
    
    // Método alternativo: usar el instalador con argumentos correctos
    $installer = file_get_contents('https://getcomposer.org/installer');
    if ($installer !== false) {
        file_put_contents('composer-setup.php', $installer);
        
        // Simular argv para el instalador
        $_SERVER['argv'] = array('composer-setup.php');
        $argc = 1;
        $argv = $_SERVER['argv'];
        
        echo "<p>Ejecutando instalador de Composer...</p>";
        ob_start();
        include 'composer-setup.php';
        $output = ob_get_clean();
        echo "<pre>$output</pre>";
        
        // Limpiar
        if (file_exists('composer-setup.php')) {
            unlink('composer-setup.php');
        }
    }
} else {
    file_put_contents('composer.phar', $composerPhar);
    echo "<p style='color:green'>✅ Composer descargado directamente</p>";
}

// Verificar que composer.phar existe
if (file_exists('composer.phar')) {
    echo "<p style='color:green'>✅ composer.phar creado correctamente</p>";
    
    // Hacer ejecutable
    chmod('composer.phar', 0755);
    
    // Verificar que funciona
    echo "<h2>🔍 Verificando Composer...</h2>";
    $versionOutput = shell_exec('php composer.phar --version 2>&1');
    echo "<pre>$versionOutput</pre>";
    
    if (strpos($versionOutput, 'Composer') !== false) {
        echo "<p style='color:green'>✅ Composer funciona correctamente</p>";
        
        // Instalar dependencias
        echo "<h2>📦 Instalando dependencias de Laravel...</h2>";
        
        // Configurar timeout y memoria
        ini_set('max_execution_time', 300); // 5 minutos
        ini_set('memory_limit', '512M');
        
        $installCommand = 'php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1';
        echo "<p><strong>Ejecutando:</strong> $installCommand</p>";
        
        $installOutput = shell_exec($installCommand);
        echo "<pre>$installOutput</pre>";
        
        // Verificar vendor/
        if (is_dir('vendor')) {
            echo "<p style='color:green'>✅ Directorio vendor/ creado correctamente</p>";
            
            // Verificar autoloader
            if (file_exists('vendor/autoload.php')) {
                echo "<p style='color:green'>✅ Autoloader disponible</p>";
                
                // Configurar .env si no existe
                if (!file_exists('.env')) {
                    if (file_exists('.env.production')) {
                        copy('.env.production', '.env');
                        echo "<p style='color:green'>✅ Archivo .env configurado desde .env.production</p>";
                    } elseif (file_exists('.env.server')) {
                        copy('.env.server', '.env');
                        echo "<p style='color:green'>✅ Archivo .env configurado desde .env.server</p>";
                    }
                }
                
                // Generar clave de aplicación
                echo "<h2>🔑 Configurando Laravel...</h2>";
                $keyOutput = shell_exec('php artisan key:generate --force 2>&1');
                echo "<pre>$keyOutput</pre>";
                
                // Cache config
                echo "<p><strong>Optimizando configuración...</strong></p>";
                $cacheOutput = shell_exec('php artisan config:cache 2>&1');
                echo "<pre>$cacheOutput</pre>";
                
                echo "<h2 style='color:green'>🎉 ¡INSTALACIÓN COMPLETA!</h2>";
                echo "<p><strong>Ahora puedes probar:</strong></p>";
                echo "<ul>";
                echo "<li><a href='public/simple.php' target='_blank'>Test simple PHP</a></li>";
                echo "<li><a href='public/ultra-test.php' target='_blank'>Ultra test diagnóstico</a></li>";
                echo "<li><a href='login' target='_blank'>Login Laravel (sin /public/)</a></li>";
                echo "<li><a href='public/login' target='_blank'>Login Laravel (con /public/)</a></li>";
                echo "<li><a href='admin/dashboard' target='_blank'>Panel Admin (sin /public/)</a></li>";
                echo "<li><a href='public/admin/dashboard' target='_blank'>Panel Admin (con /public/)</a></li>";
                echo "</ul>";
                
            } else {
                echo "<p style='color:red'>❌ No se encontró vendor/autoload.php</p>";
            }
            
        } else {
            echo "<p style='color:red'>❌ No se pudo crear el directorio vendor/</p>";
            echo "<p>Salida del comando install:</p>";
            echo "<pre>$installOutput</pre>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Composer no funciona correctamente</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ No se pudo crear composer.phar</p>";
}

echo "<hr>";
echo "<p><small>Script ejecutado desde: " . __FILE__ . "</small></p>";
echo "<p><small>Directorio HOME: " . getenv('HOME') . "</small></p>";
echo "<p><small>Directorio COMPOSER_HOME: " . getenv('COMPOSER_HOME') . "</small></p>";
?>