<?php
/**
 * ==========================================================================
 * LIMPIAR RESEÑAS DEMO DE LA BASE DE DATOS
 * Preparar para reseñas reales de Apify
 * ==========================================================================
 */

echo "=== LIMPIEZA DE RESEÑAS DEMO ===\n\n";

try {
    // Conectar a base de datos
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Análisis inicial de las reseñas
    echo "📊 ANÁLISIS DE RESEÑAS ACTUALES:\n\n";
    
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $totalReviews = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "   - Total reseñas en BD: " . number_format($totalReviews) . "\n";
    
    // Identificar reseñas demo
    $demoStmt = $pdo->query("
        SELECT COUNT(*) as demo_count
        FROM reviews 
        WHERE platform_review_id LIKE 'demo_%'
    ");
    $demoCount = $demoStmt->fetch(PDO::FETCH_ASSOC)['demo_count'];
    
    echo "   - Reseñas demo (ID con 'demo_'): " . number_format($demoCount) . "\n";
    
    // Buscar otros patrones de reseñas simuladas
    $simulatedStmt = $pdo->query("
        SELECT COUNT(*) as sim_count
        FROM reviews 
        WHERE liked_text LIKE '%simulada%' 
           OR liked_text LIKE '%demo%' 
           OR liked_text LIKE '%test%'
           OR disliked_text LIKE '%demo%'
           OR user_name = 'Usuario Demo'
           OR review_id_booking LIKE 'demo_%'
    ");
    $simulatedCount = $simulatedStmt->fetch(PDO::FETCH_ASSOC)['sim_count'];
    
    echo "   - Reseñas con contenido simulado: " . number_format($simulatedCount) . "\n";
    
    // Reseñas por plataforma
    echo "\n📈 DISTRIBUCIÓN POR PLATAFORMA:\n";
    $platformStmt = $pdo->query("
        SELECT platform, COUNT(*) as count
        FROM reviews 
        GROUP BY platform 
        ORDER BY count DESC
    ");
    
    $platforms = $platformStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($platforms as $platform) {
        echo "   - {$platform['platform']}: " . number_format($platform['count']) . " reseñas\n";
    }
    
    // Reseñas por hotel
    echo "\n🏨 DISTRIBUCIÓN POR HOTEL:\n";
    $hotelStmt = $pdo->query("
        SELECT h.nombre_hotel, COUNT(r.id) as count
        FROM reviews r
        JOIN hoteles h ON r.hotel_id = h.id
        GROUP BY r.hotel_id, h.nombre_hotel
        ORDER BY count DESC
    ");
    
    $hotels = $hotelStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hotels as $hotel) {
        echo "   - {$hotel['nombre_hotel']}: " . number_format($hotel['count']) . " reseñas\n";
    }
    
    // Fechas de las reseñas
    echo "\n📅 RANGO DE FECHAS:\n";
    $dateStmt = $pdo->query("
        SELECT 
            MIN(review_date) as fecha_min,
            MAX(review_date) as fecha_max,
            MIN(scraped_at) as scraped_min,
            MAX(scraped_at) as scraped_max
        FROM reviews
    ");
    $dates = $dateStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   - Review más antigua: {$dates['fecha_min']}\n";
    echo "   - Review más reciente: {$dates['fecha_max']}\n";
    echo "   - Primera extracción: {$dates['scraped_min']}\n";
    echo "   - Última extracción: {$dates['scraped_max']}\n";
    
    // Calcular porcentaje de reseñas demo
    $totalDemoCount = $demoCount + $simulatedCount;
    $realCount = $totalReviews - $totalDemoCount;
    $demoPercentage = ($totalDemoCount / $totalReviews) * 100;
    
    echo "\n🔍 RESUMEN:\n";
    echo "   - Reseñas reales: " . number_format($realCount) . " (" . number_format(100 - $demoPercentage, 1) . "%)\n";
    echo "   - Reseñas demo/simuladas: " . number_format($totalDemoCount) . " (" . number_format($demoPercentage, 1) . "%)\n\n";
    
    if ($totalDemoCount > 0) {
        echo "⚠️  ACCIÓN REQUERIDA: Se detectaron " . number_format($totalDemoCount) . " reseñas demo/simuladas\n\n";
        
        // Mostrar muestra de reseñas demo
        echo "📝 MUESTRA DE RESEÑAS DEMO:\n";
        $sampleStmt = $pdo->query("
            SELECT review_id_booking, user_name, liked_text, disliked_text, platform, review_date
            FROM reviews 
            WHERE review_id_booking LIKE 'demo_%' OR platform_review_id LIKE 'demo_%'
            LIMIT 3
        ");
        
        $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($samples as $i => $sample) {
            echo "   Demo " . ($i + 1) . ":\n";
            echo "      - ID: {$sample['review_id_booking']}\n";
            echo "      - Autor: {$sample['user_name']}\n";
            echo "      - Plataforma: {$sample['platform']}\n";
            echo "      - Fecha: {$sample['review_date']}\n";
            echo "      - Liked: " . substr($sample['liked_text'] ?? '', 0, 50) . "...\n";
            echo "      - Disliked: " . substr($sample['disliked_text'] ?? '', 0, 50) . "...\n\n";
        }
        
        // Preguntar si proceder con limpieza
        echo "🧹 OPCIONES DE LIMPIEZA:\n";
        echo "1. Eliminar solo reseñas con ID 'demo_*'\n";
        echo "2. Eliminar todas las reseñas (limpieza completa)\n";
        echo "3. Crear backup antes de eliminar\n";
        echo "4. Solo analizar (no eliminar nada)\n\n";
        
        echo "¿Qué acción deseas realizar?\n";
        echo "Escribe el número (1-4): ";
        
        // Para script automatizado, usar opción 3 (backup + limpieza)
        $action = 3;
        
        switch ($action) {
            case 1:
                echo "Seleccionado: Eliminar solo reseñas demo\n\n";
                $deleted = cleanDemoReviews($pdo, false);
                echo "✅ Eliminadas {$deleted} reseñas demo\n";
                break;
                
            case 2:
                echo "Seleccionado: Limpieza completa\n\n";
                $deleted = cleanAllReviews($pdo);
                echo "✅ Eliminadas {$deleted} reseñas (limpieza completa)\n";
                break;
                
            case 3:
                echo "Seleccionado: Backup + limpieza demo\n\n";
                createBackup($pdo);
                $deleted = cleanDemoReviews($pdo, true);
                echo "✅ Backup creado y {$deleted} reseñas demo eliminadas\n";
                break;
                
            case 4:
                echo "Seleccionado: Solo análisis\n";
                echo "✅ No se eliminó nada\n";
                break;
        }
        
    } else {
        echo "✅ No se detectaron reseñas demo. Base de datos limpia.\n";
    }
    
    // Verificar estado final
    echo "\n📊 ESTADO FINAL:\n";
    $finalStmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $finalTotal = $finalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "   - Reseñas restantes: " . number_format($finalTotal) . "\n";
    
    if ($finalTotal < $totalReviews) {
        $cleaned = $totalReviews - $finalTotal;
        echo "   - Reseñas eliminadas: " . number_format($cleaned) . "\n";
    }
    
    echo "\n🎯 PRÓXIMOS PASOS:\n";
    echo "1. ✅ Base de datos analizada y limpia\n";
    echo "2. 🔧 Corregir Google Place IDs de hoteles\n";
    echo "3. 🚀 Iniciar extracciones reales con Apify\n";
    echo "4. 📊 Configurar monitoreo automático\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

/**
 * Eliminar reseñas demo
 */
function cleanDemoReviews($pdo, $includeSimulated = true) {
    $conditions = [
        "platform_review_id LIKE 'demo_%'",
        "review_id_booking LIKE 'demo_%'"
    ];
    
    if ($includeSimulated) {
        $conditions[] = "liked_text LIKE '%simulada%'";
        $conditions[] = "liked_text LIKE '%demo%'";
        $conditions[] = "liked_text LIKE '%test%'";
        $conditions[] = "disliked_text LIKE '%demo%'";
        $conditions[] = "user_name = 'Usuario Demo'";
    }
    
    $whereClause = implode(" OR ", $conditions);
    
    $deleteStmt = $pdo->prepare("DELETE FROM reviews WHERE {$whereClause}");
    $deleteStmt->execute();
    
    return $deleteStmt->rowCount();
}

/**
 * Eliminar todas las reseñas
 */
function cleanAllReviews($pdo) {
    $deleteStmt = $pdo->prepare("DELETE FROM reviews");
    $deleteStmt->execute();
    
    // Resetear auto_increment
    $pdo->exec("ALTER TABLE reviews AUTO_INCREMENT = 1");
    
    return $deleteStmt->rowCount();
}

/**
 * Crear backup de reseñas
 */
function createBackup($pdo) {
    $backupDate = date('Y_m_d_H_i_s');
    $backupTable = "reviews_backup_{$backupDate}";
    
    echo "📦 Creando backup en tabla: {$backupTable}\n";
    
    $pdo->exec("CREATE TABLE {$backupTable} LIKE reviews");
    $pdo->exec("INSERT INTO {$backupTable} SELECT * FROM reviews");
    
    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM {$backupTable}");
    $backupCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   ✅ Backup completado: {$backupCount} reseñas respaldadas\n\n";
    
    return $backupTable;
}

echo "\n=== FIN LIMPIEZA ===\n";
?>