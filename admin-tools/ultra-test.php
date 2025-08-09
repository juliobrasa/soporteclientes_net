<?php
echo "<h2>🆘 Ultra Test - Diagnóstico Fundamental</h2>";

echo "<h3>1. Verificación de Archivos Críticos:</h3>";

// Verificar vendor/autoload.php
if (file_exists('../vendor/autoload.php')) {
    echo "✅ vendor/autoload.php existe<br>";
    $size = filesize('../vendor/autoload.php');
    echo "📏 Tamaño: " . number_format($size) . " bytes<br>";
    
    if ($size > 1000) {
        echo "✅ Tamaño del autoloader es normal<br>";
    } else {
        echo "❌ Autoloader parece corrupto (muy pequeño)<br>";
    }
} else {
    echo "❌ vendor/autoload.php NO EXISTE<br>";
}

// Verificar directorio vendor
if (is_dir('../vendor')) {
    echo "✅ Directorio vendor/ existe<br>";
    $files = scandir('../vendor');
    echo "📁 Archivos en vendor/: " . count($files) . "<br>";
    
    // Verificar subdirectorios importantes
    $important_dirs = ['laravel', 'illuminate', 'composer'];
    foreach ($important_dirs as $dir) {
        if (is_dir("../vendor/$dir")) {
            echo "✅ vendor/$dir/ existe<br>";
        } else {
            echo "❌ vendor/$dir/ NO existe<br>";
        }
    }
} else {
    echo "❌ Directorio vendor/ NO EXISTE<br>";
}

echo "<h3>2. Verificación de Bootstrap:</h3>";
if (file_exists('../bootstrap/app.php')) {
    echo "✅ bootstrap/app.php existe<br>";
    $content = file_get_contents('../bootstrap/app.php');
    if (strpos($content, 'Application::configure') !== false) {
        echo "✅ Bootstrap tiene contenido Laravel 11<br>";
    } else {
        echo "⚠️ Bootstrap puede estar corrupto<br>";
    }
} else {
    echo "❌ bootstrap/app.php NO existe<br>";
}

echo "<h3>3. Verificación de Composer:</h3>";
if (file_exists('../composer.json')) {
    echo "✅ composer.json existe<br>";
    $composer = json_decode(file_get_contents('../composer.json'), true);
    if (isset($composer['require']['laravel/framework'])) {
        echo "✅ Laravel framework en composer.json<br>";
    }
} else {
    echo "❌ composer.json NO existe<br>";
}

if (file_exists('../composer.lock')) {
    echo "✅ composer.lock existe (dependencias instaladas)<br>";
} else {
    echo "❌ composer.lock NO existe (dependencias no instaladas)<br>";
}

echo "<h3>4. Intento de Cargar Autoloader:</h3>";
try {
    require_once '../vendor/autoload.php';
    echo "✅ Autoloader cargado sin errores<br>";
    
    // Verificar si podemos cargar clases básicas de Laravel
    if (class_exists('Illuminate\\Foundation\\Application')) {
        echo "✅ Clase Application de Laravel disponible<br>";
    } else {
        echo "❌ Clase Application NO disponible<br>";
    }
    
} catch (Error $e) {
    echo "❌ ERROR FATAL cargando autoloader: " . $e->getMessage() . "<br>";
    echo "📍 Archivo: " . $e->getFile() . " línea " . $e->getLine() . "<br>";
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN cargando autoloader: " . $e->getMessage() . "<br>";
}

echo "<h3>5. Verificación de Permisos:</h3>";
$dirs = ['../vendor', '../bootstrap', '../storage', '../config'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "✅ $dir es legible<br>";
        } else {
            echo "❌ $dir NO es legible<br>";
        }
    }
}

echo "<h3>6. Información del Sistema:</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
echo "Script Filename: " . __FILE__ . "<br>";

echo "<hr>";
echo "<h3>🎯 Diagnóstico:</h3>";

if (!file_exists('../vendor/autoload.php')) {
    echo "<div style='background: #ffe6e6; padding: 10px; border: 1px solid #ff0000; border-radius: 5px;'>";
    echo "<strong>❌ PROBLEMA CRÍTICO:</strong> Composer no está instalado o vendor/ fue eliminado.<br>";
    echo "<strong>SOLUCIÓN:</strong> Ejecutar <code>composer install</code> en el directorio raíz de Laravel.";
    echo "</div>";
} elseif (!is_dir('../vendor/laravel')) {
    echo "<div style='background: #ffe6e6; padding: 10px; border: 1px solid #ff0000; border-radius: 5px;'>";
    echo "<strong>❌ PROBLEMA CRÍTICO:</strong> Dependencias de Laravel no están instaladas completamente.<br>";
    echo "<strong>SOLUCIÓN:</strong> Ejecutar <code>composer install --no-dev</code> en el directorio raíz de Laravel.";
    echo "</div>";
} else {
    echo "<div style='background: #e6ffe6; padding: 10px; border: 1px solid #00ff00; border-radius: 5px;'>";
    echo "<strong>✅ Archivos básicos OK.</strong> El problema puede estar en la configuración o permisos.";
    echo "</div>";
}
?>