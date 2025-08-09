<?php
/**
 * Monitor Simple Post-Migración
 * Verificación básica del estado del sistema después de la migración
 */

require_once 'env-loader.php';

echo "🔍 MONITOREO POST-MIGRACIÓN - SIMPLE\n";
echo str_repeat("=", 50) . "\n\n";

// Verificar base de datos
echo "📊 1. VERIFICANDO BASE DE DATOS:\n";
try {
    $pdo = createDatabaseConnection();
    echo "✅ Conexión a BD exitosa\n";
    
    // Contar reviews por fuente
    $stmt = $pdo->query("
        SELECT 
            COALESCE(extraction_source, 'legacy') as source,
            COUNT(*) as count
        FROM reviews 
        GROUP BY COALESCE(extraction_source, 'legacy')
    ");
    $sources = $stmt->fetchAll();
    
    echo "📈 Reviews por fuente:\n";
    foreach ($sources as $source) {
        echo "   - {$source['source']}: {$source['count']} reviews\n";
    }
    
    // Verificar triggers
    $stmt = $pdo->query("SHOW TRIGGERS WHERE `Table` = 'reviews'");
    $triggers = $stmt->fetchAll();
    echo "🔧 Triggers activos: " . count($triggers) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "\n";
}

echo "\n📡 2. VERIFICANDO API:\n";
try {
    // Test directo de la API
    $_GET = ['limit' => '1']; // Simular parámetros
    
    ob_start();
    include __DIR__ . '/api/reviews.php';
    $apiOutput = ob_get_clean();
    
    $apiData = json_decode($apiOutput, true);
    
    if ($apiData && isset($apiData['success']) && $apiData['success']) {
        echo "✅ API funcionando correctamente\n";
        echo "📊 Total reviews: " . $apiData['pagination']['total'] . "\n";
        echo "🔢 API version: " . ($apiData['meta']['api_version'] ?? 'unknown') . "\n";
        echo "🔄 Schema unificado: " . ($apiData['meta']['unified_schema'] ? 'SÍ' : 'NO') . "\n";
        
        if (!empty($apiData['data'])) {
            $firstReview = $apiData['data'][0];
            echo "📝 Último review: {$firstReview['guest']} ({$firstReview['extraction_source']})\n";
        }
    } else {
        echo "❌ API no responde correctamente\n";
        if (isset($apiData['error'])) {
            echo "   Error: " . $apiData['error'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error de API: " . $e->getMessage() . "\n";
}

echo "\n🔧 3. VERIFICANDO PROCESADOR APIFY:\n";
if (file_exists(__DIR__ . '/apify-data-processor.php')) {
    echo "✅ Procesador Apify presente\n";
    $lastModified = filemtime(__DIR__ . '/apify-data-processor.php');
    echo "📅 Última modificación: " . date('Y-m-d H:i:s', $lastModified) . "\n";
} else {
    echo "❌ Procesador Apify no encontrado\n";
}

echo "\n🎯 4. VERIFICANDO ESQUEMA UNIFICADO:\n";
try {
    // Verificar columnas críticas
    $stmt = $pdo->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $criticalColumns = [
        'source_platform', 'platform',
        'property_response', 'response_from_owner', 
        'rating', 'normalized_rating',
        'review_text', 'extraction_source'
    ];
    
    $present = array_intersect($criticalColumns, $columns);
    $missing = array_diff($criticalColumns, $columns);
    
    echo "✅ Columnas presentes: " . count($present) . "/" . count($criticalColumns) . "\n";
    
    if (!empty($missing)) {
        echo "❌ Columnas faltantes: " . implode(', ', $missing) . "\n";
    }
    
    // Test de sincronización
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM reviews 
        WHERE (source_platform IS NOT NULL AND platform IS NOT NULL)
           OR (rating IS NOT NULL AND normalized_rating IS NOT NULL)
    ");
    $syncedCount = $stmt->fetch()['total'];
    
    echo "🔄 Registros con campos sincronizados: $syncedCount\n";
    
} catch (Exception $e) {
    echo "❌ Error verificando esquema: " . $e->getMessage() . "\n";
}

echo "\n📊 5. ESTADÍSTICAS RÁPIDAS:\n";
try {
    // Reviews recientes
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM reviews 
        WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $recentCount = $stmt->fetch()['count'];
    echo "📈 Reviews últimas 24h: $recentCount\n";
    
    // Rating promedio
    $stmt = $pdo->query("
        SELECT ROUND(AVG(COALESCE(rating, normalized_rating)), 2) as avg_rating
        FROM reviews
    ");
    $avgRating = $stmt->fetch()['avg_rating'];
    echo "⭐ Rating promedio: $avgRating/10\n";
    
    // Plataformas activas
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT COALESCE(source_platform, platform)) as platforms
        FROM reviews
    ");
    $platformCount = $stmt->fetch()['platforms'];
    echo "🌐 Plataformas activas: $platformCount\n";
    
} catch (Exception $e) {
    echo "❌ Error en estadísticas: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Determinar estado general
$issues = 0;
if (!isset($pdo)) $issues++;
if (!isset($apiData) || !$apiData['success']) $issues++;
if (!file_exists(__DIR__ . '/apify-data-processor.php')) $issues++;
if (!empty($missing)) $issues++;

if ($issues === 0) {
    echo "🎉 ESTADO GENERAL: EXCELENTE - Sistema funcionando perfectamente\n";
    echo "✅ Migración exitosa - No se detectaron problemas\n";
} elseif ($issues <= 2) {
    echo "⚠️  ESTADO GENERAL: BUENO - Problemas menores detectados\n";
    echo "🔧 Se recomienda revisar los elementos marcados con ❌\n";
} else {
    echo "🚨 ESTADO GENERAL: REQUIERE ATENCIÓN - Múltiples problemas\n";
    echo "🛠️  Se requiere intervención inmediata\n";
}

echo "\n💡 RECOMENDACIONES:\n";
echo "- Ejecutar este monitor cada 4-6 horas durante las próximas 48h\n";
echo "- Verificar logs de errores si hay problemas\n";
echo "- Usar 'php verify-reviews-schema.php' para verificación detallada\n";

echo "\nMonitoreo completado: " . date('Y-m-d H:i:s') . "\n";
?>