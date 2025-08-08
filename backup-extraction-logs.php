<?php
/**
 * BACKUP Y MANTENIMIENTO DE LOGS
 */

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    echo "[" . date("Y-m-d H:i:s") . "] Iniciando mantenimiento de logs\n";
    
    // Limpiar logs antiguos (más de 30 días)
    $cleanStmt = $pdo->prepare("
        DELETE FROM extraction_logs 
        WHERE execution_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $cleanStmt->execute();
    $cleaned = $cleanStmt->rowCount();
    
    echo "[" . date("Y-m-d H:i:s") . "] Logs antiguos limpiados: {$cleaned}\n";
    
    // Optimizar tablas
    $pdo->exec("OPTIMIZE TABLE extraction_logs");
    $pdo->exec("OPTIMIZE TABLE extraction_config");
    
    echo "[" . date("Y-m-d H:i:s") . "] Tablas optimizadas\n";
    
    // Reporte de estadísticas mensuales
    $monthlyStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(execution_date, \"%Y-%m\") as month,
            platform,
            COUNT(*) as executions,
            SUM(reviews_extracted) as total_reviews,
            SUM(cost_estimate) as total_cost
        FROM extraction_logs
        WHERE execution_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY month, platform
        ORDER BY month DESC, platform
    ");
    
    $monthlyStats = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "[" . date("Y-m-d H:i:s") . "] === ESTADÍSTICAS MENSUALES ===\n";
    foreach ($monthlyStats as $stat) {
        echo "   {$stat[\"month\"]} - {$stat[\"platform\"]}: {$stat[\"total_reviews\"]} reseñas, ${$stat[\"total_cost\"]}\n";
    }
    
} catch (Exception $e) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: " . $e->getMessage() . "\n";
}
?>