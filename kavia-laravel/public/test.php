<?php
// Test básico para verificar que PHP funciona
echo "✅ PHP está funcionando correctamente<br>";
echo "📅 Fecha: " . date('Y-m-d H:i:s') . "<br>";
echo "🔧 Versión PHP: " . PHP_VERSION . "<br>";
echo "📁 Directorio actual: " . __DIR__ . "<br>";

// Test de permisos
if (is_writable('../storage')) {
    echo "✅ Directorio storage tiene permisos de escritura<br>";
} else {
    echo "❌ Directorio storage NO tiene permisos de escritura<br>";
}

// Test de base de datos (intentar conectar)
try {
    // Leer configuración
    $envFile = '../.env';
    if (file_exists($envFile)) {
        echo "✅ Archivo .env encontrado<br>";
        $env = file_get_contents($envFile);
        if (strpos($env, 'DB_HOST') !== false) {
            echo "✅ Configuración de base de datos encontrada en .env<br>";
        }
    } else {
        echo "❌ Archivo .env NO encontrado<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>🚀 <a href='/kavia-laravel/public/login'>Ir al Login de Laravel</a>";
?>