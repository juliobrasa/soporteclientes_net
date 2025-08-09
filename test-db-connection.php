<?php
/**
 * Test específico de conexión de base de datos
 */
echo "🔧 TEST DE CONEXIÓN DE BASE DE DATOS\n";
echo str_repeat("=", 50) . "\n\n";

echo "1. Verificando archivos de entorno:\n";
$envFiles = ['.env.local', '.env', '.env.production'];
foreach ($envFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file - existe\n";
    } else {
        echo "❌ $file - NO existe\n";
    }
}

echo "\n2. Probando carga de env-loader.php:\n";
try {
    require_once 'env-loader.php';
    echo "✅ env-loader.php cargado correctamente\n";
    
    echo "\n3. Verificando variables de entorno:\n";
    $dbVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
    foreach ($dbVars as $var) {
        $value = EnvironmentLoader::get($var, 'NOT_SET');
        if ($value === 'NOT_SET') {
            echo "❌ $var - NO CONFIGURADA\n";
        } else {
            echo "✅ $var - " . (strlen($value) > 0 ? 'configurada' : 'vacía') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error cargando env-loader: " . $e->getMessage() . "\n";
}

echo "\n4. Probando admin-config.php:\n";
try {
    require_once 'admin-config.php';
    echo "✅ admin-config.php cargado correctamente\n";
    
    if (function_exists('getDBConnection')) {
        echo "✅ Función getDBConnection existe\n";
        
        echo "\n5. Probando conexión real:\n";
        $pdo = getDBConnection();
        
        if ($pdo) {
            echo "✅ Conexión de BD exitosa\n";
            
            // Test simple query
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            if ($result && $result['test'] == 1) {
                echo "✅ Query de prueba exitosa\n";
            } else {
                echo "❌ Query de prueba falló\n";
            }
            
        } else {
            echo "❌ Conexión de BD falló - getDBConnection() devolvió null\n";
        }
        
    } else {
        echo "❌ Función getDBConnection NO existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error probando admin-config: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "💥 Error fatal: " . $e->getMessage() . "\n";
}

echo "\n💡 CONCLUSIÓN:\n";
echo "Si ves errores arriba, esos son los problemas que impiden la conexión a BD\n";
echo "Si todo está ✅, entonces el problema está en el contexto específico de api-extraction.php\n";
?>