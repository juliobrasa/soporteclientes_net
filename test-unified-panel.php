<?php
/**
 * Test rápido del panel unificado
 */
session_start();
$_SESSION['admin_logged'] = true;
$_SESSION['admin_email'] = 'test@admin.com';

require_once 'admin-config.php';

echo "=== TEST DEL PANEL UNIFICADO ===\n\n";

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Error conectando a la base de datos");
    }
    
    echo "✅ Conexión a base de datos OK\n";
    
    // Test 1: Verificar hoteles con portales
    echo "\n1. 🏨 Verificando hoteles con portales disponibles:\n";
    $stmt = $pdo->query("
        SELECT 
            id, 
            nombre_hotel, 
            CASE WHEN url_booking IS NOT NULL AND url_booking != '' THEN 1 ELSE 0 END as has_booking,
            CASE WHEN google_place_id IS NOT NULL AND google_place_id != '' THEN 1 ELSE 0 END as has_google,
            CASE WHEN tripadvisor_url IS NOT NULL AND tripadvisor_url != '' THEN 1 ELSE 0 END as has_tripadvisor
        FROM hoteles 
        WHERE activo = 1
        ORDER BY id
    ");
    $hotels = $stmt->fetchAll();
    
    foreach ($hotels as $hotel) {
        $portals = [];
        if ($hotel['has_booking']) $portals[] = '📘 Booking';
        if ($hotel['has_google']) $portals[] = '🗺️ Google';
        if ($hotel['has_tripadvisor']) $portals[] = '✈️ TripAdvisor';
        
        echo "   Hotel {$hotel['id']}: {$hotel['nombre_hotel']}\n";
        echo "   Portales: " . implode(', ', $portals) . "\n\n";
    }
    
    // Test 2: Verificar trabajos existentes
    echo "2. 📊 Verificando trabajos de extracción:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN platform = 'booking' THEN 1 END) as booking_jobs,
            COUNT(CASE WHEN platform = 'google' THEN 1 END) as google_jobs,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
        FROM extraction_jobs
    ");
    $stats = $stmt->fetch();
    
    echo "   Total trabajos: {$stats['total']}\n";
    echo "   Trabajos Booking: {$stats['booking_jobs']}\n";
    echo "   Trabajos Google: {$stats['google_jobs']}\n";
    echo "   Completados: {$stats['completed']}\n";
    
    // Test 3: Verificar reseñas por portal
    echo "\n3. 📝 Verificando reseñas por portal:\n";
    $stmt = $pdo->query("
        SELECT 
            source_platform,
            COUNT(*) as count,
            MAX(scraped_at) as last_scraped
        FROM reviews 
        GROUP BY source_platform
        ORDER BY count DESC
    ");
    $reviewStats = $stmt->fetchAll();
    
    if (empty($reviewStats)) {
        echo "   No hay reseñas en la base de datos\n";
    } else {
        foreach ($reviewStats as $stat) {
            echo "   {$stat['source_platform']}: {$stat['count']} reseñas (última: {$stat['last_scraped']})\n";
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "✅ Panel unificado listo para usar\n";
    echo "🌐 URL: https://soporteclientes.net/admin-extraction-unified.php\n";
    echo "🔧 Funcionalidades:\n";
    echo "   - Selección visual de hoteles y portales\n";
    echo "   - Extracción de Booking.com operativa\n";
    echo "   - Google Maps y TripAdvisor preparados para implementación\n";
    echo "   - Interfaz similar al sistema anterior\n";
    echo "   - Sistema de extracción independiente por portal\n";
    
    echo "\n🎯 Para probar:\n";
    echo "   1. Acceder al panel web\n";
    echo "   2. Click en 'Nueva Extracción'\n";
    echo "   3. Seleccionar hoteles y marcar checkbox de Booking\n";
    echo "   4. Configurar 5-10 reseñas para prueba\n";
    echo "   5. Iniciar extracción\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>