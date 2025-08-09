<?php
echo "<h1>🔍 Test Básico Laravel</h1>";

echo "<h2>1. Verificación de Archivos:</h2>";

// Verificar vendor/autoload.php
if (file_exists('../vendor/autoload.php')) {
    echo "✅ vendor/autoload.php existe<br>";
} else {
    echo "❌ vendor/autoload.php NO EXISTE<br>";
}

// Verificar .env
if (file_exists('../.env')) {
    echo "✅ .env existe<br>";
} else {
    echo "❌ .env NO existe<br>";
}

// Verificar composer.phar
if (file_exists('../composer.phar')) {
    echo "✅ composer.phar existe<br>";
} else {
    echo "❌ composer.phar NO existe<br>";
}

echo "<h2>2. Estado:</h2>";
if (file_exists('../vendor/autoload.php')) {
    echo "✅ Laravel puede funcionar<br>";
    echo "<a href='login'>Probar Login</a><br>";
} else {
    echo "❌ Necesitas ejecutar: <a href='../install-composer-fixed.php'>install-composer-fixed.php</a><br>";
}

echo "<p>Directorio: " . getcwd() . "</p>";
?>