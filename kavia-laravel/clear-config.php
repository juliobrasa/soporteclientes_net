<?php
/**
 * 🔧 Script para limpiar y regenerar configuración sin SSH
 * Ejecutar desde: /public_html/web/
 * Comando: php clear-config.php
 */

echo "🔧 LIMPIEZA DE CONFIGURACIÓN LARAVEL\n";
echo "===================================\n\n";

$baseDir = __DIR__;

// 1. Limpiar cache de configuración
$configCache = $baseDir . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    if (unlink($configCache)) {
        echo "✅ Cache de configuración eliminado\n";
    } else {
        echo "❌ No se pudo eliminar cache de configuración\n";
    }
} else {
    echo "ℹ️ No existe cache de configuración\n";
}

// 2. Limpiar cache de rutas
$routeCache = $baseDir . '/bootstrap/cache/routes-v7.php';
if (file_exists($routeCache)) {
    if (unlink($routeCache)) {
        echo "✅ Cache de rutas eliminado\n";
    } else {
        echo "❌ No se pudo eliminar cache de rutas\n";
    }
} else {
    echo "ℹ️ No existe cache de rutas\n";
}

// 3. Limpiar cache de servicios
$servicesCache = $baseDir . '/bootstrap/cache/services.php';
if (file_exists($servicesCache)) {
    if (unlink($servicesCache)) {
        echo "✅ Cache de servicios eliminado\n";
    } else {
        echo "❌ No se pudo eliminar cache de servicios\n";
    }
} else {
    echo "ℹ️ No existe cache de servicios\n";
}

// 4. Verificar configuración actual de .env
echo "\n📄 Verificando configuración .env:\n";
if (file_exists($baseDir . '/.env')) {
    $envContent = file_get_contents($baseDir . '/.env');
    
    // Extraer configuración de BD
    $dbLines = [];
    foreach (explode("\n", $envContent) as $line) {
        if (strpos($line, 'DB_') === 0) {
            $dbLines[] = $line;
        }
    }
    
    foreach ($dbLines as $line) {
        echo "   $line\n";
    }
} else {
    echo "❌ Archivo .env no encontrado\n";
}

// 5. Probar conexión MySQL directa
echo "\n🗄️ Probando conexión MySQL directa:\n";
try {
    $host = 'localhost';
    $dbname = 'soporteia_bookingkavia';
    $username = 'soporteia_admin';
    $password = 'QCF8RhS*}.Oj0u(v';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM hoteles");
    $result = $stmt->fetch();
    echo "✅ MySQL directo OK - Hoteles: " . $result['total'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Error MySQL: " . $e->getMessage() . "\n";
}

// 6. Crear archivo de prueba sin Laravel
echo "\n🧪 Creando prueba directa MySQL:\n";
$mysqlTest = '<?php
header("Content-Type: application/json");
try {
    $host = "localhost";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $pdo->query("SELECT * FROM hoteles ORDER BY id DESC");
    $hoteles = $stmt->fetchAll();
    
    echo json_encode([
        "success" => true,
        "message" => "MySQL directo funcionando",
        "hotels_count" => count($hoteles),
        "hotels" => $hoteles
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>';

if (file_put_contents($baseDir . '/public/mysql-direct-test.php', $mysqlTest)) {
    echo "✅ Prueba MySQL directa creada: /public/mysql-direct-test.php\n";
}

echo "\n✅ Limpieza completada!\n";
echo "\nPrueba ahora:\n";
echo "🔹 MySQL Directo: https://soporteclientes.net/web/public/mysql-direct-test.php\n";
echo "🔹 API Hotels: https://soporteclientes.net/web/public/api/hotels\n";
?>