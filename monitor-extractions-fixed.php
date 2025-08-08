<?php
/**
 * SCRIPT DE MONITOREO DE EXTRACCIONES (CORREGIDO)
 */

require_once __DIR__ . "/env-loader.php";

echo "=== MONITOREO DE EXTRACCIONES ===\n\n";

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Estadísticas de las últimas 24 horas
    echo "📊 ESTADÍSTICAS ÚLTIMAS 24 HORAS:\n";
    
    $statsStmt = $pdo->query("
        SELECT 
            platform,
            COUNT(*) as total_runs,
            SUM(reviews_extracted) as total_reviews,
            AVG(execution_time) as avg_time,
            SUM(cost_estimate) as total_cost,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
            SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count
        FROM extraction_logs 
        WHERE execution_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY platform
        ORDER BY total_reviews DESC
    ");
    
    $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stats)) {
        echo "   📝 No hay extracciones registradas en las últimas 24 horas\n\n";
    } else {
        foreach ($stats as $stat) {
            echo "   {$stat['platform']}:\n";
            echo "     - Ejecuciones: {$stat['total_runs']}\n";
            echo "     - Reseñas extraídas: {$stat['total_reviews']}\n";
            echo "     - Tiempo promedio: " . round($stat['avg_time']) . "s\n";
            echo "     - Costo: $" . number_format($stat['total_cost'], 4) . "\n";
            echo "     - Éxito: {$stat['success_count']} | Errores: {$stat['error_count']}\n\n";
        }
    }
    
    // Próximas extracciones programadas
    echo "⏰ PRÓXIMAS EXTRACCIONES:\n";
    
    $nextStmt = $pdo->query("
        SELECT ec.platform, h.nombre_hotel, ec.next_extraction, ec.max_reviews_per_run
        FROM extraction_config ec
        JOIN hoteles h ON ec.hotel_id = h.id
        WHERE ec.enabled = 1
        ORDER BY ec.next_extraction ASC
        LIMIT 10
    ");
    
    $nextExtractions = $nextStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($nextExtractions as $next) {
        echo "   - {$next['next_extraction']}: {$next['platform']} para {$next['nombre_hotel']} (max {$next['max_reviews_per_run']} reseñas)\n";
    }
    
    // Estado de salud del sistema
    echo "\n🏥 ESTADO DEL SISTEMA:\n";
    
    $healthStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_configs,
            SUM(enabled) as active_configs,
            COUNT(DISTINCT hotel_id) as hotels_configured
        FROM extraction_config
    ");
    
    $health = $healthStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   - Configuraciones totales: {$health['total_configs']}\n";
    echo "   - Configuraciones activas: {$health['active_configs']}\n";
    echo "   - Hoteles configurados: {$health['hotels_configured']}\n";
    
    // Total de reseñas en la base de datos
    $reviewsStmt = $pdo->query("SELECT COUNT(*) as total_reviews FROM reviews");
    $totalReviews = $reviewsStmt->fetch(PDO::FETCH_ASSOC)['total_reviews'];
    echo "   - Total reseñas en BD: " . number_format($totalReviews) . "\n";
    
    // Últimos errores
    $errorStmt = $pdo->query("
        SELECT platform, error_message, execution_date
        FROM extraction_logs 
        WHERE status = 'error' 
        AND execution_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY execution_date DESC
        LIMIT 5
    ");
    
    $errors = $errorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($errors)) {
        echo "\n⚠️  ERRORES RECIENTES:\n";
        foreach ($errors as $error) {
            echo "   - {$error['execution_date']}: {$error['platform']} - {$error['error_message']}\n";
        }
    } else {
        echo "\n✅ Sin errores recientes\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN MONITOREO ===\n";
?>