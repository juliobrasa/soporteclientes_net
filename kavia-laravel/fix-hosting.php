<?php
/**
 * 🔧 Script Automatizado de Diagnóstico y Corrección
 * Para hosting soporteclientes.net/web
 * 
 * Ejecutar desde: /public_html/web/
 * Comando: php fix-hosting.php
 */

echo "🚀 SCRIPT DE DIAGNÓSTICO Y CORRECCIÓN KAVIA LARAVEL\n";
echo "==================================================\n\n";

$baseDir = __DIR__;
$errors = [];
$fixes = [];

// ================================================================
// 1. VERIFICAR ESTRUCTURA BÁSICA
// ================================================================
echo "📁 1. Verificando estructura básica...\n";

$requiredDirs = [
    'app', 'bootstrap', 'config', 'database', 'public', 
    'resources', 'routes', 'storage', 'vendor'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($baseDir . '/' . $dir)) {
        $errors[] = "❌ Falta directorio: $dir";
    } else {
        echo "✅ Directorio $dir existe\n";
    }
}

// ================================================================
// 2. VERIFICAR ARCHIVOS CRÍTICOS
// ================================================================
echo "\n📄 2. Verificando archivos críticos...\n";

$requiredFiles = [
    '.env' => 'Configuración principal',
    'artisan' => 'CLI de Laravel',
    'composer.json' => 'Dependencias',
    'routes/api.php' => 'Rutas API',
    'app/Models/Hotel.php' => 'Modelo Hotel',
    'app/Http/Controllers/API/HotelController.php' => 'Controller Hotel'
];

foreach ($requiredFiles as $file => $desc) {
    if (!file_exists($baseDir . '/' . $file)) {
        $errors[] = "❌ Falta archivo: $file ($desc)";
    } else {
        echo "✅ $desc: $file\n";
    }
}

// ================================================================
// 3. VERIFICAR PERMISOS
// ================================================================
echo "\n🔐 3. Verificando y corrigiendo permisos...\n";

$permissionDirs = [
    'storage' => 0755,
    'storage/logs' => 0775,
    'storage/framework' => 0755,
    'storage/framework/cache' => 0755,
    'storage/framework/sessions' => 0755,
    'storage/framework/views' => 0755,
    'bootstrap/cache' => 0755
];

foreach ($permissionDirs as $dir => $perm) {
    $fullPath = $baseDir . '/' . $dir;
    if (is_dir($fullPath)) {
        if (chmod($fullPath, $perm)) {
            echo "✅ Permisos corregidos: $dir ($perm)\n";
            $fixes[] = "Permisos corregidos en $dir";
        } else {
            $errors[] = "❌ No se pudieron cambiar permisos de $dir";
        }
    }
}

// ================================================================
// 4. VERIFICAR CONFIGURACIÓN .ENV
// ================================================================
echo "\n⚙️ 4. Verificando configuración .env...\n";

if (file_exists($baseDir . '/.env')) {
    $envContent = file_get_contents($baseDir . '/.env');
    
    $envChecks = [
        'APP_KEY=' => 'Clave de aplicación',
        'DB_CONNECTION=mysql' => 'Conexión MySQL',
        'DB_DATABASE=soporteia_bookingkavia' => 'Base de datos correcta',
        'DB_USERNAME=soporteia_admin' => 'Usuario BD correcto'
    ];
    
    foreach ($envChecks as $check => $desc) {
        if (strpos($envContent, $check) !== false) {
            echo "✅ $desc configurado\n";
        } else {
            $errors[] = "❌ Falta configuración: $desc";
        }
    }
    
    // Verificar APP_KEY específicamente
    if (preg_match('/APP_KEY=base64:(.+)/', $envContent, $matches)) {
        if (strlen($matches[1]) < 40) {
            $errors[] = "❌ APP_KEY parece inválida (muy corta)";
        } else {
            echo "✅ APP_KEY parece válida\n";
        }
    } else {
        $errors[] = "❌ APP_KEY no encontrada o inválida";
    }
}

// ================================================================
// 5. PROBAR AUTOLOAD Y BOOTSTRAP
// ================================================================
echo "\n🔄 5. Probando carga de Laravel...\n";

try {
    if (file_exists($baseDir . '/vendor/autoload.php')) {
        require_once $baseDir . '/vendor/autoload.php';
        echo "✅ Autoload cargado correctamente\n";
        
        if (file_exists($baseDir . '/bootstrap/app.php')) {
            $app = require_once $baseDir . '/bootstrap/app.php';
            echo "✅ Bootstrap de Laravel cargado\n";
            
            // Probar configuración
            if (function_exists('config')) {
                $dbConnection = config('database.default', 'NO_CONFIG');
                echo "✅ Configuración cargada - DB: $dbConnection\n";
            }
        } else {
            $errors[] = "❌ No se pudo cargar bootstrap/app.php";
        }
    } else {
        $errors[] = "❌ No se pudo cargar vendor/autoload.php - ejecutar composer install";
    }
} catch (Exception $e) {
    $errors[] = "❌ Error cargando Laravel: " . $e->getMessage();
}

// ================================================================
// 6. PROBAR CONEXIÓN A BASE DE DATOS
// ================================================================
echo "\n🗄️ 6. Probando conexión a base de datos...\n";

try {
    $host = 'localhost';
    $dbname = 'soporteia_bookingkavia';
    $username = 'soporteia_admin';
    $password = 'QCF8RhS*}.Oj0u(v';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Conexión MySQL directa OK\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM hoteles");
    $result = $stmt->fetch();
    echo "✅ Hoteles encontrados: " . $result['total'] . "\n";
    
} catch (Exception $e) {
    $errors[] = "❌ Error de conexión MySQL: " . $e->getMessage();
}

// ================================================================
// 7. CREAR ARCHIVOS DE PRUEBA
// ================================================================
echo "\n🧪 7. Creando archivos de prueba...\n";

// Crear archivo de prueba API simple
$simpleApiTest = '<?php
header("Content-Type: application/json");
try {
    echo json_encode([
        "message" => "🚀 Prueba API simple funcionando!",
        "timestamp" => date("Y-m-d H:i:s"),
        "status" => "OK"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage(),
        "status" => "ERROR"
    ]);
}
?>';

if (file_put_contents($baseDir . '/public/api-simple-test.php', $simpleApiTest)) {
    echo "✅ Archivo de prueba API creado: /public/api-simple-test.php\n";
    $fixes[] = "Archivo de prueba API creado";
}

// Crear archivo de prueba Laravel completo
$laravelTest = '<?php
header("Content-Type: application/json");
try {
    require_once "../vendor/autoload.php";
    $app = require_once "../bootstrap/app.php";
    
    // Usar Facade DB si está disponible
    if (class_exists("Illuminate\\Support\\Facades\\DB")) {
        $count = DB::table("hoteles")->count();
        echo json_encode([
            "message" => "Laravel DB funcionando!",
            "hotels_count" => $count,
            "status" => "OK"
        ]);
    } else {
        echo json_encode([
            "message" => "Laravel cargado pero sin DB facade",
            "status" => "PARTIAL"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine(),
        "status" => "ERROR"
    ]);
}
?>';

if (file_put_contents($baseDir . '/public/laravel-db-test.php', $laravelTest)) {
    echo "✅ Archivo de prueba Laravel DB creado: /public/laravel-db-test.php\n";
    $fixes[] = "Archivo de prueba Laravel DB creado";
}

// ================================================================
// 8. COMANDOS SUGERIDOS
// ================================================================
echo "\n📋 8. Comandos sugeridos para ejecutar:\n";

$suggestedCommands = [
    'composer install --no-dev --optimize-autoloader',
    'php artisan key:generate',
    'php artisan config:cache',
    'php artisan route:cache',
    'php artisan view:cache'
];

foreach ($suggestedCommands as $cmd) {
    echo "   💡 $cmd\n";
}

// ================================================================
// 9. RESUMEN FINAL
// ================================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RESUMEN FINAL\n";
echo str_repeat("=", 50) . "\n";

if (count($fixes) > 0) {
    echo "✅ CORRECCIONES APLICADAS:\n";
    foreach ($fixes as $fix) {
        echo "   • $fix\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "❌ PROBLEMAS ENCONTRADOS:\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
    echo "\n";
    echo "🔧 ACCIONES RECOMENDADAS:\n";
    echo "   1. Ejecutar: composer install --no-dev --optimize-autoloader\n";
    echo "   2. Ejecutar: php artisan key:generate\n";
    echo "   3. Ejecutar: php artisan config:cache\n";
    echo "   4. Probar: https://soporteclientes.net/web/public/api-simple-test.php\n";
    echo "   5. Probar: https://soporteclientes.net/web/public/laravel-db-test.php\n";
} else {
    echo "🎉 ¡TODO PARECE ESTAR CORRECTO!\n";
    echo "\n🧪 PRUEBAS DISPONIBLES:\n";
    echo "   • https://soporteclientes.net/web/public/api-simple-test.php\n";
    echo "   • https://soporteclientes.net/web/public/laravel-db-test.php\n";
    echo "   • https://soporteclientes.net/web/public/api/test\n";
    echo "   • https://soporteclientes.net/web/public/api/hotels\n";
}

echo "\n📱 URLs DE PRUEBA FINALES:\n";
echo "   🔹 API Simple: https://soporteclientes.net/web/public/api-simple-test.php\n";
echo "   🔹 Laravel DB: https://soporteclientes.net/web/public/laravel-db-test.php\n";
echo "   🔹 API Test: https://soporteclientes.net/web/public/api/test\n";
echo "   🔹 API Hotels: https://soporteclientes.net/web/public/api/hotels\n";

echo "\n✅ Script completado!\n";
?>