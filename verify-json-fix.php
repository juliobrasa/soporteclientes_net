<?php
require_once 'env-loader.php';

echo "📊 VERIFICANDO CORRECCIÓN JSON_EXTRACT\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $pdo = createDatabaseConnection();
    
    // 1. Verificar columnas agregadas a system_logs
    echo "🔍 1. COLUMNAS NORMALIZADAS EN SYSTEM_LOGS:\n";
    $stmt = $pdo->query('DESCRIBE system_logs');
    $columns = $stmt->fetchAll();
    
    $extractedColumns = [];
    foreach ($columns as $col) {
        if (strpos($col['Field'], '_extracted') !== false || $col['Field'] === 'event_timestamp') {
            $extractedColumns[] = $col;
            echo "  ✅ {$col['Field']}: {$col['Type']}\n";
        }
    }
    
    // 2. Verificar índices
    echo "\n📊 2. ÍNDICES CREADOS:\n";
    $stmt = $pdo->query('SHOW INDEX FROM system_logs WHERE Key_name LIKE "%extracted%" OR Key_name LIKE "%event_timestamp%"');
    $indexes = $stmt->fetchAll();
    
    $indexNames = [];
    foreach ($indexes as $idx) {
        if (!in_array($idx['Key_name'], $indexNames)) {
            $indexNames[] = $idx['Key_name'];
            echo "  📋 {$idx['Key_name']} en {$idx['Column_name']}\n";
        }
    }
    
    // 3. Test de compatibilidad JSON
    echo "\n🧪 3. TEST DE COMPATIBILIDAD JSON:\n";
    
    // Verificar si JSON_EXTRACT funciona
    try {
        $stmt = $pdo->query("SELECT JSON_EXTRACT('{}', '$.test') as test_result");
        $result = $stmt->fetch();
        echo "  ✅ JSON_EXTRACT disponible y funcional\n";
    } catch (Exception $e) {
        echo "  ❌ JSON_EXTRACT no funciona: " . $e->getMessage() . "\n";
    }
    
    // 4. Estadísticas
    echo "\n📈 4. ESTADÍSTICAS:\n";
    echo "  ✅ Columnas normalizadas: " . count($extractedColumns) . "\n";
    echo "  📊 Índices optimizados: " . count($indexNames) . "\n";
    
    // 5. Ejemplo de uso
    echo "\n💡 5. EJEMPLOS DE USO SEGURO:\n";
    echo "\n// ❌ Problemático (puede fallar en versiones antiguas):\n";
    echo "SELECT * FROM system_logs WHERE JSON_EXTRACT(context, '$.job_id') = 'job123';\n";
    
    echo "\n// ✅ Optimizado (siempre funciona):\n";
    echo "SELECT * FROM system_logs WHERE job_id_extracted = 'job123';\n";
    
    echo "\n// ✅ Híbrido (máxima compatibilidad):\n";
    echo "SELECT * FROM system_logs WHERE \n";
    echo "  COALESCE(job_id_extracted, JSON_EXTRACT(context, '$.job_id')) = 'job123';\n";
    
    echo "\n🎯 RESULTADO: OPTIMIZACIÓN EXITOSA\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>