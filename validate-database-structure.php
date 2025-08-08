<?php
/**
 * Script para validar y corregir la estructura de base de datos
 * para el sistema de extracción por portales
 */
require_once 'admin-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("❌ Error conectando a la base de datos\n");
    }
    
    echo "=== VALIDACIÓN DE ESTRUCTURA DE BASE DE DATOS ===\n\n";
    
    // 1. Verificar tabla extraction_jobs
    echo "1. Verificando tabla extraction_jobs...\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE extraction_jobs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $existingColumns = array_column($columns, 'Field');
        echo "   ✅ Tabla extraction_jobs existe\n";
        
        // Verificar columnas necesarias
        $requiredColumns = [
            'platform' => "VARCHAR(50) DEFAULT 'general' AFTER hotel_id",
            'apify_run_id' => "VARCHAR(100) NULL AFTER platform"
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $existingColumns)) {
                echo "   ❌ Columna '$column' faltante. Agregando...\n";
                $pdo->exec("ALTER TABLE extraction_jobs ADD COLUMN $column $definition");
                echo "   ✅ Columna '$column' agregada correctamente\n";
            } else {
                echo "   ✅ Columna '$column' existe\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "   ❌ Error verificando extraction_jobs: " . $e->getMessage() . "\n";
    }
    
    // 2. Verificar tabla hoteles
    echo "\n2. Verificando tabla hoteles...\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE hoteles");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $existingColumns = array_column($columns, 'Field');
        echo "   ✅ Tabla hoteles existe\n";
        
        // Verificar columnas para URLs de portales
        $urlColumns = [
            'booking_url' => "TEXT NULL COMMENT 'URL de Booking.com'",
            'google_place_id' => "VARCHAR(255) NULL COMMENT 'Google Place ID'",
            'tripadvisor_url' => "TEXT NULL COMMENT 'URL de TripAdvisor'"
        ];
        
        foreach ($urlColumns as $column => $definition) {
            if (!in_array($column, $existingColumns)) {
                echo "   ❌ Columna '$column' faltante. Agregando...\n";
                $pdo->exec("ALTER TABLE hoteles ADD COLUMN $column $definition");
                echo "   ✅ Columna '$column' agregada correctamente\n";
            } else {
                echo "   ✅ Columna '$column' existe\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "   ❌ Error verificando hoteles: " . $e->getMessage() . "\n";
    }
    
    // 3. Verificar tabla reviews
    echo "\n3. Verificando tabla reviews...\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE reviews");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✅ Tabla reviews existe\n";
        
        // Verificar que tenga las columnas necesarias para multi-portal
        $existingColumns = array_column($columns, 'Field');
        $reviewColumns = ['source_platform', 'platform_review_id', 'unique_id'];
        
        foreach ($reviewColumns as $column) {
            if (in_array($column, $existingColumns)) {
                echo "   ✅ Columna '$column' existe\n";
            } else {
                echo "   ❌ Columna '$column' faltante en reviews\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "   ❌ Error verificando reviews: " . $e->getMessage() . "\n";
    }
    
    // 4. Estadísticas de datos
    echo "\n=== ESTADÍSTICAS DE DATOS ===\n";
    
    // Estadísticas de hoteles
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_hoteles,
            COUNT(CASE WHEN booking_url IS NOT NULL AND booking_url != '' THEN 1 END) as con_booking,
            COUNT(CASE WHEN url_booking IS NOT NULL AND url_booking != '' THEN 1 END) as con_url_booking,
            COUNT(CASE WHEN google_place_id IS NOT NULL AND google_place_id != '' THEN 1 END) as con_google_place_id,
            COUNT(CASE WHEN tripadvisor_url IS NOT NULL AND tripadvisor_url != '' THEN 1 END) as con_tripadvisor
        FROM hoteles WHERE activo = 1
    ");
    $hotelStats = $stmt->fetch();
    
    echo "Hoteles activos: {$hotelStats['total_hoteles']}\n";
    echo "- Con booking_url: {$hotelStats['con_booking']}\n";
    echo "- Con url_booking: {$hotelStats['con_url_booking']}\n";
    echo "- Con Google Place ID: {$hotelStats['con_google_place_id']}\n";
    echo "- Con TripAdvisor URL: {$hotelStats['con_tripadvisor']}\n";
    
    $totalWithBooking = $hotelStats['con_booking'] + $hotelStats['con_url_booking'];
    echo "- Total con URLs de Booking: {$totalWithBooking}\n";
    
    // Estadísticas de trabajos de extracción
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_jobs,
            COUNT(CASE WHEN platform = 'booking' THEN 1 END) as booking_jobs,
            COUNT(CASE WHEN platform = 'google' THEN 1 END) as google_jobs,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_jobs,
            SUM(reviews_extracted) as total_reviews
        FROM extraction_jobs
    ");
    $jobStats = $stmt->fetch();
    
    echo "\nTrabajos de extracción: {$jobStats['total_jobs']}\n";
    echo "- Trabajos de Booking: {$jobStats['booking_jobs']}\n";
    echo "- Trabajos de Google: {$jobStats['google_jobs']}\n";
    echo "- Trabajos completados: {$jobStats['completed_jobs']}\n";
    echo "- Total reseñas extraídas: {$jobStats['total_reviews']}\n";
    
    // 5. Verificar hoteles con URLs para pruebas
    echo "\n=== HOTELES LISTOS PARA EXTRACCIÓN ===\n";
    
    $stmt = $pdo->query("
        SELECT id, nombre_hotel, booking_url, url_booking 
        FROM hoteles 
        WHERE activo = 1 
        AND (booking_url IS NOT NULL OR url_booking IS NOT NULL)
        AND (booking_url != '' OR url_booking != '')
        ORDER BY nombre_hotel 
        LIMIT 10
    ");
    $hotelsReady = $stmt->fetchAll();
    
    if (!empty($hotelsReady)) {
        echo "Hoteles listos para extracción de Booking:\n";
        foreach ($hotelsReady as $hotel) {
            $url = $hotel['booking_url'] ?: $hotel['url_booking'];
            echo "- ID {$hotel['id']}: {$hotel['nombre_hotel']}\n";
            echo "  URL: " . substr($url, 0, 60) . "...\n";
        }
    } else {
        echo "❌ No hay hoteles con URLs de Booking configuradas\n";
        echo "💡 Ejecuta add-booking-urls.php para agregar URLs de ejemplo\n";
    }
    
    // 6. Validación final
    echo "\n=== VALIDACIÓN FINAL ===\n";
    
    $issues = [];
    
    if ($totalWithBooking == 0) {
        $issues[] = "No hay hoteles con URLs de Booking configuradas";
    }
    
    if ($jobStats['total_jobs'] == 0) {
        $issues[] = "No hay trabajos de extracción en la base de datos";
    }
    
    if (empty($issues)) {
        echo "✅ Base de datos lista para el sistema de extracción por portales\n";
        echo "🚀 Puedes empezar a usar admin-extraction-unified.php o admin-extraction-portals.php\n";
    } else {
        echo "⚠️  Problemas encontrados:\n";
        foreach ($issues as $issue) {
            echo "- $issue\n";
        }
    }
    
    echo "\n=== VALIDACIÓN COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>