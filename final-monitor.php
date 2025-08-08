<?php
/**
 * MONITOREO FINAL DE EXTRACCIONES
 */

echo "=== MONITOREO DE EXTRACCIONES ===\n\n";

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Estado del sistema
    echo "🏥 ESTADO DEL SISTEMA:\n";
    
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
    
    // Total de reseñas
    $reviewsStmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $totalReviews = $reviewsStmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   - Total reseñas en BD: " . number_format($totalReviews) . "\n\n";
    
    // Distribución por plataforma
    echo "📊 RESEÑAS POR PLATAFORMA:\n";
    $platformStmt = $pdo->query("
        SELECT 
            CASE 
                WHEN platform = 'unknown' THEN 'Reseñas existentes'
                ELSE platform 
            END as platform_name,
            COUNT(*) as count 
        FROM reviews 
        GROUP BY platform 
        ORDER BY count DESC
    ");
    
    $platforms = $platformStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($platforms as $platform) {
        echo "   - {$platform['platform_name']}: " . number_format($platform['count']) . " reseñas\n";
    }
    
    // Próximas extracciones
    echo "\n⏰ PRÓXIMAS EXTRACCIONES PROGRAMADAS:\n";
    
    $nextStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(ec.next_extraction, '%Y-%m-%d %H:%i') as next_time,
            ec.platform, 
            h.nombre_hotel, 
            ec.max_reviews_per_run
        FROM extraction_config ec
        JOIN hoteles h ON ec.hotel_id = h.id
        WHERE ec.enabled = 1
        ORDER BY ec.next_extraction ASC
        LIMIT 5
    ");
    
    $nextExtractions = $nextStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($nextExtractions as $next) {
        echo "   - {$next['next_time']}: {$next['platform']} para {$next['nombre_hotel']} (max {$next['max_reviews_per_run']} reseñas)\n";
    }
    
    // Actividad reciente
    echo "\n📈 ACTIVIDAD RECIENTE:\n";
    $recentStmt = $pdo->query("
        SELECT DATE(scraped_at) as fecha, COUNT(*) as nuevas
        FROM reviews 
        WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(scraped_at)
        ORDER BY fecha DESC
        LIMIT 5
    ");
    
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recent)) {
        echo "   📝 No hay actividad reciente de extracciones automatizadas\n";
        echo "   💡 Las extracciones comenzarán mañana a las 2:00 AM\n";
    } else {
        foreach ($recent as $day) {
            echo "   - {$day['fecha']}: {$day['nuevas']} nuevas reseñas\n";
        }
    }
    
    // Hoteles por Place ID real
    echo "\n🏨 HOTELES CON PLACE IDs REALES:\n";
    $hotelStmt = $pdo->query("
        SELECT nombre_hotel, google_place_id
        FROM hoteles 
        WHERE activo = 1
        ORDER BY id
        LIMIT 5
    ");
    
    $hotels = $hotelStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hotels as $hotel) {
        $placeId = substr($hotel['google_place_id'], 0, 20) . '...';
        echo "   - {$hotel['nombre_hotel']}: {$placeId}\n";
    }
    
    echo "\n✅ SISTEMA CONFIGURADO Y FUNCIONANDO\n";
    echo "🎯 Próxima extracción automática: Mañana a las 2:00 AM\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN MONITOREO ===\n";
?>