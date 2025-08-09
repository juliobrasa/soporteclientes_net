<?php
require_once 'env-loader.php';

echo "🔍 DEBUG: URLS DE BOOKING EN HOTELES\n\n";

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    
    $stmt = $pdo->query("
        SELECT 
            id,
            nombre_hotel,
            url_booking,
            booking_url,
            CASE 
                WHEN (url_booking IS NOT NULL AND url_booking != '') OR (booking_url IS NOT NULL AND booking_url != '') THEN 1 ELSE 0 
            END as has_booking,
            activo
        FROM hoteles 
        WHERE activo = 1
        ORDER BY nombre_hotel
    ");
    
    $hotels = $stmt->fetchAll();
    
    echo "📊 TOTAL DE HOTELES ACTIVOS: " . count($hotels) . "\n\n";
    
    $withBooking = 0;
    $withoutBooking = 0;
    
    foreach ($hotels as $hotel) {
        $hasUrl = !empty($hotel['url_booking']) || !empty($hotel['booking_url']);
        $urlDisplay = $hotel['url_booking'] ?: $hotel['booking_url'] ?: 'NINGUNA';
        
        if ($hasUrl) {
            $withBooking++;
            echo "✅ {$hotel['nombre_hotel']} (ID: {$hotel['id']})\n";
            echo "   URL: {$urlDisplay}\n\n";
        } else {
            $withoutBooking++;
            echo "❌ {$hotel['nombre_hotel']} (ID: {$hotel['id']})\n";
            echo "   url_booking: " . ($hotel['url_booking'] ?: 'NULL/vacío') . "\n";
            echo "   booking_url: " . ($hotel['booking_url'] ?: 'NULL/vacío') . "\n\n";
        }
    }
    
    echo "📋 RESUMEN:\n";
    echo "✅ Hoteles CON URL de Booking: {$withBooking}\n";
    echo "❌ Hoteles SIN URL de Booking: {$withoutBooking}\n";
    
    if ($withoutBooking > 0) {
        echo "\n⚠️ ACCIÓN REQUERIDA:\n";
        echo "Configure URLs de Booking para los hoteles marcados con ❌\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>