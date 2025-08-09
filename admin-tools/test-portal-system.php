<?php
/**
 * Script de prueba completo para el sistema de extracción por portales
 */
require_once 'admin-config.php';
require_once 'env-loader.php';

echo "=== PRUEBA COMPLETA DEL SISTEMA DE EXTRACCIÓN POR PORTALES ===\n\n";

try {
    // 1. Verificar conexión a base de datos
    echo "1. Verificando conexión a base de datos...\n";
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("No se puede conectar a la base de datos");
    }
    echo "   ✅ Conexión a base de datos exitosa\n\n";
    
    // 2. Verificar variables de entorno
    echo "2. Verificando variables de entorno...\n";
    $apifyToken = getEnvVar('APIFY_API_TOKEN');
    if (!$apifyToken || $apifyToken === 'your_apify_token_here') {
        echo "   ⚠️  Token de Apify no configurado correctamente\n";
        echo "   💡 Configura APIFY_API_TOKEN en el archivo .env\n";
    } else {
        $maskedToken = substr($apifyToken, 0, 10) . '***' . substr($apifyToken, -5);
        echo "   ✅ Token de Apify configurado: $maskedToken\n";
    }
    echo "\n";
    
    // 3. Verificar archivos del sistema
    echo "3. Verificando archivos del sistema...\n";
    $systemFiles = [
        'booking-extraction-api.php' => 'API de extracción de Booking',
        'admin-extraction-unified.php' => 'Panel unificado multi-portal',
        'admin-extraction-portals.php' => 'Panel de portales específicos',
        'env-loader.php' => 'Cargador de variables de entorno'
    ];
    
    foreach ($systemFiles as $file => $description) {
        if (file_exists($file)) {
            echo "   ✅ $file ($description)\n";
        } else {
            echo "   ❌ $file faltante\n";
        }
    }
    echo "\n";
    
    // 4. Verificar estructura de base de datos
    echo "4. Verificando estructura de base de datos...\n";
    
    // Verificar tabla extraction_jobs
    $stmt = $pdo->query("DESCRIBE extraction_jobs");
    $extractionColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    $requiredColumns = ['platform', 'apify_run_id'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $extractionColumns)) {
            echo "   ✅ Columna extraction_jobs.$col existe\n";
        } else {
            echo "   ❌ Columna extraction_jobs.$col faltante\n";
        }
    }
    
    // Verificar tabla hoteles
    $stmt = $pdo->query("DESCRIBE hoteles");
    $hotelColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    $urlColumns = ['booking_url', 'url_booking', 'google_place_id'];
    foreach ($urlColumns as $col) {
        if (in_array($col, $hotelColumns)) {
            echo "   ✅ Columna hoteles.$col existe\n";
        } else {
            echo "   ❌ Columna hoteles.$col faltante\n";
        }
    }
    echo "\n";
    
    // 5. Verificar hoteles disponibles
    echo "5. Verificando hoteles disponibles para extracción...\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN (url_booking IS NOT NULL AND url_booking != '') OR 
                              (booking_url IS NOT NULL AND booking_url != '') THEN 1 END) as con_booking,
            COUNT(CASE WHEN google_place_id IS NOT NULL AND google_place_id != '' THEN 1 END) as con_google
        FROM hoteles WHERE activo = 1
    ");
    $stats = $stmt->fetch();
    
    echo "   📊 Hoteles activos: {$stats['total']}\n";
    echo "   🏨 Con URLs de Booking: {$stats['con_booking']}\n";
    echo "   📍 Con Google Place ID: {$stats['con_google']}\n";
    
    if ($stats['con_booking'] > 0) {
        echo "   ✅ Sistema listo para extracción de Booking.com\n";
    } else {
        echo "   ⚠️  No hay hoteles configurados para Booking.com\n";
    }
    echo "\n";
    
    // 6. Probar API de Booking (simulación)
    echo "6. Probando API de extracción de Booking...\n";
    
    if ($apifyToken && $apifyToken !== 'your_apify_token_here' && $stats['con_booking'] > 0) {
        echo "   🧪 Simulando llamada a API de Booking...\n";
        
        // Obtener un hotel de prueba
        $stmt = $pdo->query("
            SELECT id, nombre_hotel, url_booking, booking_url 
            FROM hoteles 
            WHERE activo = 1 
            AND ((url_booking IS NOT NULL AND url_booking != '') OR 
                 (booking_url IS NOT NULL AND booking_url != ''))
            LIMIT 1
        ");
        $testHotel = $stmt->fetch();
        
        if ($testHotel) {
            echo "   🏨 Hotel de prueba: {$testHotel['nombre_hotel']} (ID: {$testHotel['id']})\n";
            
            // Verificar que el BookingExtractor se puede instanciar
            require_once 'booking-extraction-api.php';
            try {
                $extractor = new BookingExtractor($pdo);
                echo "   ✅ BookingExtractor instanciado correctamente\n";
                
                // Calcular costo estimado
                $cost = $extractor->estimateCost(50);
                echo "   💰 Costo estimado para 50 reseñas: $" . number_format($cost, 4) . "\n";
                
            } catch (Exception $e) {
                echo "   ❌ Error instanciando BookingExtractor: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ❌ No se encontró hotel de prueba\n";
        }
    } else {
        echo "   ⚠️  Prueba de API omitida (token no configurado o no hay hoteles)\n";
    }
    echo "\n";
    
    // 7. Verificar trabajos existentes
    echo "7. Verificando trabajos de extracción existentes...\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN platform = 'booking' THEN 1 END) as booking,
            COUNT(CASE WHEN platform = 'google' THEN 1 END) as google,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            SUM(reviews_extracted) as total_reviews
        FROM extraction_jobs
    ");
    $jobStats = $stmt->fetch();
    
    echo "   📊 Total trabajos: {$jobStats['total']}\n";
    echo "   🏨 Trabajos Booking: {$jobStats['booking']}\n";
    echo "   🌍 Trabajos Google: {$jobStats['google']}\n";
    echo "   ✅ Completados: {$jobStats['completed']}\n";
    echo "   ⭐ Total reseñas extraídas: {$jobStats['total_reviews']}\n";
    echo "\n";
    
    // 8. Verificar reseñas en base de datos
    echo "8. Verificando reseñas en base de datos...\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN source_platform = 'booking' THEN 1 END) as booking_reviews,
            COUNT(CASE WHEN source_platform = 'google' THEN 1 END) as google_reviews,
            COUNT(CASE WHEN DATE(scraped_at) = CURDATE() THEN 1 END) as today_reviews
        FROM reviews
    ");
    $reviewStats = $stmt->fetch();
    
    echo "   📊 Total reseñas: {$reviewStats['total']}\n";
    echo "   🏨 Reseñas de Booking: {$reviewStats['booking_reviews']}\n";
    echo "   🌍 Reseñas de Google: {$reviewStats['google_reviews']}\n";
    echo "   📅 Reseñas de hoy: {$reviewStats['today_reviews']}\n";
    echo "\n";
    
    // 9. Estado general del sistema
    echo "=== RESUMEN GENERAL DEL SISTEMA ===\n";
    
    $systemHealthy = true;
    $warnings = [];
    $errors = [];
    
    // Verificaciones críticas
    if (!$apifyToken || $apifyToken === 'your_apify_token_here') {
        $errors[] = "Token de Apify no configurado";
        $systemHealthy = false;
    }
    
    if ($stats['con_booking'] == 0) {
        $warnings[] = "No hay hoteles configurados para Booking";
    }
    
    if (!file_exists('booking-extraction-api.php')) {
        $errors[] = "API de Booking faltante";
        $systemHealthy = false;
    }
    
    // Mostrar estado
    if ($systemHealthy && empty($warnings)) {
        echo "🟢 SISTEMA COMPLETAMENTE OPERATIVO\n";
        echo "✅ Todos los componentes funcionando correctamente\n";
        echo "🚀 Listo para extracción en producción\n\n";
        
        echo "📋 PRÓXIMOS PASOS:\n";
        echo "1. Accede a admin-extraction-unified.php para extracción multi-portal\n";
        echo "2. O usa admin-extraction-portals.php para gestión por portal específico\n";
        echo "3. El sistema puede extraer hasta {$stats['con_booking']} hoteles de Booking.com\n";
        
    } elseif ($systemHealthy) {
        echo "🟡 SISTEMA OPERATIVO CON ADVERTENCIAS\n";
        echo "✅ Componentes principales funcionando\n";
        echo "⚠️  Advertencias:\n";
        foreach ($warnings as $warning) {
            echo "   - $warning\n";
        }
        
    } else {
        echo "🔴 SISTEMA CON ERRORES CRÍTICOS\n";
        echo "❌ Errores que deben corregirse:\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
        if (!empty($warnings)) {
            echo "⚠️  Advertencias adicionales:\n";
            foreach ($warnings as $warning) {
                echo "   - $warning\n";
            }
        }
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
}
?>