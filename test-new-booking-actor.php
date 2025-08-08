<?php
/**
 * Prueba del nuevo actor de Booking más estable
 */

require_once 'env-loader.php';
require_once 'apify-config.php';

// Cargar variables de entorno
loadEnvFile();

echo "=== PRUEBA DEL NUEVO ACTOR DE BOOKING ===\n\n";

try {
    // Crear cliente Apify
    $apifyClient = new ApifyClient();
    
    echo "ℹ️  Información del cliente:\n";
    $debugInfo = $apifyClient->getDebugInfo();
    foreach ($debugInfo as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    echo "\n";
    
    // Configurar extracción específica para Booking con nuevo actor
    $config = [
        'hotel_id' => 7, // Hotel Ambiance
        'platforms' => ['booking'], // Solo Booking
        'maxReviews' => 10, // Solo 10 reviews para prueba rápida
        'languages' => ['en', 'es'],
        'timeout' => 120 // 2 minutos timeout para prueba
    ];
    
    echo "🏨 Configuración de prueba:\n";
    echo "   - Hotel ID: {$config['hotel_id']}\n";
    echo "   - Plataformas: " . implode(', ', $config['platforms']) . "\n";
    echo "   - Máx reseñas: {$config['maxReviews']}\n";
    echo "   - Timeout: {$config['timeout']}s\n\n";
    
    echo "🚀 Iniciando extracción con nuevo actor...\n";
    $startTime = time();
    
    $result = $apifyClient->runHotelExtractionSync($config, $config['timeout']);
    
    $executionTime = time() - $startTime;
    
    echo "\n📊 RESULTADOS:\n";
    echo "   - Éxito: " . ($result['success'] ? '✅ SÍ' : '❌ NO') . "\n";
    echo "   - Tiempo ejecución: {$executionTime}s\n";
    echo "   - Reseñas obtenidas: " . ($result['reviews_count'] ?? 0) . "\n";
    
    if (isset($result['data']) && is_array($result['data']) && count($result['data']) > 0) {
        echo "\n📝 MUESTRA DE PRIMERA RESEÑA:\n";
        $firstReview = $result['data'][0];
        if (is_array($firstReview)) {
            foreach (array_slice($firstReview, 0, 8) as $field => $value) {
                $displayValue = is_string($value) ? substr($value, 0, 80) . (strlen($value) > 80 ? '...' : '') : json_encode($value);
                echo "   {$field}: {$displayValue}\n";
            }
        }
        
        echo "\n✅ NUEVO ACTOR FUNCIONANDO CORRECTAMENTE!\n";
        echo "🎉 El actor booking-scraper ha extraído reseñas exitosamente\n";
    } else {
        echo "\n⚠️  No se obtuvieron reseñas\n";
        if (!$result['success']) {
            echo "❌ ERROR:\n";
            if (isset($result['error'])) {
                echo "   " . $result['error'] . "\n";
            }
            if (isset($result['data'])) {
                echo "   Datos: " . json_encode($result['data']) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>