<?php
/**
 * Script para agregar URLs de Booking.com de ejemplo a los hoteles
 */
require_once 'admin-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Error conectando a la base de datos\n");
    }
    
    echo "=== AGREGANDO URLs DE BOOKING A HOTELES ===\n\n";
    
    // URLs de ejemplo de hoteles reales en Cancún
    $bookingUrls = [
        'luma' => 'https://www.booking.com/hotel/mx/luma-cancun.html',
        'plaza kokai' => 'https://www.booking.com/hotel/mx/plaza-kokai-cancun.html',
        'kavia plus' => 'https://www.booking.com/hotel/mx/kavia-plus-cancun.html',
        'kavia cancun' => 'https://www.booking.com/hotel/mx/kavia-cancun-downtown.html',
        'imperial las perlas' => 'https://www.booking.com/hotel/mx/imperial-las-perlas-cancun.html',
        'hacienca cancun' => 'https://www.booking.com/hotel/mx/hacienda-cancun.html',
        'xbalamque' => 'https://www.booking.com/hotel/mx/xbalamque-resort-spa.html',
        'ambiance' => 'https://www.booking.com/hotel/mx/ambiance-suites-cancun.html',
        'caribe internacional' => 'https://www.booking.com/hotel/mx/caribe-internacional.html'
    ];
    
    // Obtener hoteles existentes
    $stmt = $pdo->query("SELECT id, nombre_hotel, url_booking FROM hoteles WHERE activo = 1");
    $hotels = $stmt->fetchAll();
    
    $updatedCount = 0;
    
    foreach ($hotels as $hotel) {
        $hotelName = strtolower($hotel['nombre_hotel']);
        
        // Buscar URL correspondiente
        $matchedUrl = null;
        foreach ($bookingUrls as $pattern => $url) {
            if (strpos($hotelName, $pattern) !== false || strpos($pattern, $hotelName) !== false) {
                $matchedUrl = $url;
                break;
            }
        }
        
        if ($matchedUrl && empty($hotel['url_booking'])) {
            $stmt = $pdo->prepare("UPDATE hoteles SET url_booking = ? WHERE id = ?");
            $stmt->execute([$matchedUrl, $hotel['id']]);
            
            echo "✅ Hotel ID {$hotel['id']} ({$hotel['nombre_hotel']}): {$matchedUrl}\n";
            $updatedCount++;
        } elseif (!empty($hotel['url_booking'])) {
            echo "ℹ️  Hotel ID {$hotel['id']} ({$hotel['nombre_hotel']}): Ya tiene URL\n";
        } else {
            echo "❌ Hotel ID {$hotel['id']} ({$hotel['nombre_hotel']}): No se encontró URL coincidente\n";
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "- Hoteles actualizados: {$updatedCount}\n";
    echo "- Total hoteles: " . count($hotels) . "\n";
    
    // Verificar resultados
    echo "\n=== VERIFICACIÓN ===\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN url_booking IS NOT NULL AND url_booking != '' THEN 1 END) as con_url
        FROM hoteles WHERE activo = 1
    ");
    $stats = $stmt->fetch();
    
    echo "- Hoteles activos: {$stats['total']}\n";
    echo "- Con URL de Booking: {$stats['con_url']}\n";
    echo "- Porcentaje: " . round(($stats['con_url'] / $stats['total']) * 100, 1) . "%\n";
    
    if ($stats['con_url'] > 0) {
        echo "\n✅ ¡Sistema listo para pruebas de extracción de Booking!\n";
    } else {
        echo "\n❌ No hay hoteles con URLs de Booking configuradas\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>