<?php
/**
 * Script para ejecutar limpieza de datos demo
 */

require_once 'env-loader.php';

echo "🧹 INICIANDO LIMPIEZA DE DATOS DEMO...\n\n";

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    echo "✅ Conectado a la base de datos\n\n";
    
    // Contar datos antes de la limpieza
    echo "📊 ESTADO ANTES DE LA LIMPIEZA:\n";
    $stmt = $pdo->query("SELECT 'Reviews' as tabla, COUNT(*) as cantidad FROM reviews");
    $result = $stmt->fetch();
    echo "- Reviews: {$result['cantidad']}\n";
    
    $stmt = $pdo->query("SELECT 'Jobs' as tabla, COUNT(*) as cantidad FROM extraction_jobs");
    $result = $stmt->fetch();
    echo "- Extraction Jobs: {$result['cantidad']}\n";
    
    $stmt = $pdo->query("SELECT 'Runs' as tabla, COUNT(*) as cantidad FROM apify_extraction_runs");
    $result = $stmt->fetch();
    echo "- Apify Runs: {$result['cantidad']}\n";
    
    if ($pdo->query("SHOW TABLES LIKE 'debug_logs'")->rowCount() > 0) {
        $stmt = $pdo->query("SELECT 'Logs' as tabla, COUNT(*) as cantidad FROM debug_logs");
        $result = $stmt->fetch();
        echo "- Debug Logs: {$result['cantidad']}\n";
    }
    
    echo "\n🧹 EJECUTANDO LIMPIEZA...\n\n";
    
    // 1. Eliminar reseñas demo
    echo "1️⃣ Limpiando reseñas demo...\n";
    $stmt = $pdo->prepare("
        DELETE FROM reviews WHERE 
            user_name LIKE '%Anónimo%' OR
            user_name LIKE '%Usuario%' OR
            user_name LIKE '%Ejemplo%' OR
            unique_id LIKE '%booking_%' OR
            unique_id LIKE '%example_%' OR
            unique_id LIKE '%demo_%' OR
            unique_id LIKE '%test_%' OR
            hotel_name LIKE '%Ejemplo%' OR
            hotel_name LIKE '%Test%' OR
            hotel_name LIKE '%Demo%' OR
            review_text = '' OR
            (review_text IS NULL AND (liked_text = '' OR liked_text IS NULL) AND (disliked_text = '' OR disliked_text IS NULL))
    ");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "   ❌ Eliminadas {$deleted} reseñas demo\n";
    
    // 2. Eliminar jobs de prueba
    echo "2️⃣ Limpiando extraction jobs de prueba...\n";
    $stmt = $pdo->prepare("
        DELETE FROM extraction_jobs WHERE 
            created_at > '2025-01-01' AND
            (platforms LIKE '%test%' OR 
             platforms LIKE '%demo%' OR
             platforms LIKE '%ejemplo%')
    ");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "   ❌ Eliminados {$deleted} jobs de prueba\n";
    
    // 3. Eliminar runs de prueba
    echo "3️⃣ Limpiando apify runs de prueba...\n";
    $stmt = $pdo->prepare("
        DELETE FROM apify_extraction_runs WHERE 
            apify_run_id LIKE '%test_%' OR
            apify_run_id LIKE '%demo_%' OR
            apify_run_id LIKE '%example_%'
    ");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "   ❌ Eliminados {$deleted} runs de prueba\n";
    
    // 4. Limpiar logs antiguos
    if ($pdo->query("SHOW TABLES LIKE 'debug_logs'")->rowCount() > 0) {
        echo "4️⃣ Limpiando logs de debug antiguos...\n";
        $stmt = $pdo->prepare("
            DELETE FROM debug_logs WHERE 
                created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) OR
                message LIKE '%ejemplo%' OR
                message LIKE '%test%' OR
                message LIKE '%demo%'
        ");
        $stmt->execute();
        $deleted = $stmt->rowCount();
        echo "   ❌ Eliminados {$deleted} logs antiguos/demo\n";
    }
    
    echo "\n📊 ESTADO DESPUÉS DE LA LIMPIEZA:\n";
    
    $stmt = $pdo->query("SELECT 'Reviews' as tabla, COUNT(*) as cantidad FROM reviews");
    $result = $stmt->fetch();
    echo "- Reviews restantes: {$result['cantidad']}\n";
    
    $stmt = $pdo->query("SELECT 'Jobs' as tabla, COUNT(*) as cantidad FROM extraction_jobs");
    $result = $stmt->fetch();
    echo "- Jobs restantes: {$result['cantidad']}\n";
    
    $stmt = $pdo->query("SELECT 'Runs' as tabla, COUNT(*) as cantidad FROM apify_extraction_runs");
    $result = $stmt->fetch();
    echo "- Runs restantes: {$result['cantidad']}\n";
    
    if ($pdo->query("SHOW TABLES LIKE 'debug_logs'")->rowCount() > 0) {
        $stmt = $pdo->query("SELECT 'Logs' as tabla, COUNT(*) as cantidad FROM debug_logs");
        $result = $stmt->fetch();
        echo "- Logs restantes: {$result['cantidad']}\n";
    }
    
    // Mostrar muestra de lo que queda
    echo "\n📋 MUESTRA DE DATOS RESTANTES:\n";
    $stmt = $pdo->query("SELECT user_name, hotel_name, source_platform, created_at FROM reviews ORDER BY created_at DESC LIMIT 5");
    $reviews = $stmt->fetchAll();
    
    if ($reviews) {
        echo "Últimas 5 reseñas:\n";
        foreach ($reviews as $review) {
            $date = $review['created_at'] ?? 'N/A';
            echo "  • {$review['user_name']} - {$review['hotel_name']} ({$review['source_platform']}) - {$date}\n";
        }
    } else {
        echo "  ℹ️ No hay reseñas en la base de datos (esperado si es instalación nueva)\n";
    }
    
    echo "\n✅ LIMPIEZA COMPLETADA EXITOSAMENTE\n";
    echo "🎯 La base de datos ahora contiene solo datos reales (sin demos)\n";
    
} catch (Exception $e) {
    echo "❌ Error ejecutando limpieza: " . $e->getMessage() . "\n";
    exit(1);
}
?>