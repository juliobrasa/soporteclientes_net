<?php
/**
 * Prueba del sistema de extracción de Booking corregido
 */

require_once 'env-loader.php';
require_once 'apify-config.php';

// Cargar variables de entorno
loadEnvFile();

echo "=== PRUEBA DEL SISTEMA CORREGIDO DE BOOKING ===\n\n";

try {
    // Crear cliente Apify
    $apifyClient = new ApifyClient();
    
    echo "ℹ️  Información del cliente:\n";
    $debugInfo = $apifyClient->getDebugInfo();
    foreach ($debugInfo as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    echo "\n";
    
    // Configurar extracción específica para Booking
    $config = [
        'hotel_id' => 7, // Hotel Ambiance
        'platforms' => ['booking'], // Solo Booking
        'maxReviews' => 5,
        'languages' => ['en', 'es'],
        'timeout' => 60
    ];
    
    echo "🏨 Configuración de prueba:\n";
    echo "   - Hotel ID: {$config['hotel_id']}\n";
    echo "   - Plataformas: " . implode(', ', $config['platforms']) . "\n";
    echo "   - Máx reseñas: {$config['maxReviews']}\n";
    echo "   - Timeout: {$config['timeout']}s\n\n";
    
    echo "🚀 Iniciando extracción síncrona de Booking...\n";
    $startTime = time();
    
    $result = $apifyClient->runHotelExtractionSync($config, $config['timeout']);
    
    $executionTime = time() - $startTime;
    
    echo "\n📊 RESULTADOS:\n";
    echo "   - Éxito: " . ($result['success'] ? '✅ SÍ' : '❌ NO') . "\n";
    echo "   - Tiempo ejecución: {$executionTime}s\n";
    echo "   - Reseñas obtenidas: " . ($result['reviews_count'] ?? 0) . "\n";
    
    if (isset($result['data']) && is_array($result['data']) && count($result['data']) > 0) {
        echo "\n📝 MUESTRA DE RESEÑAS:\n";
        foreach (array_slice($result['data'], 0, 2) as $i => $review) {
            echo "   Reseña " . ($i + 1) . ":\n";
            if (is_array($review)) {
                foreach ($review as $field => $value) {
                    $displayValue = is_string($value) ? substr($value, 0, 80) . (strlen($value) > 80 ? '...' : '') : json_encode($value);
                    echo "     {$field}: {$displayValue}\n";
                }
            } else {
                echo "     " . json_encode($review) . "\n";
            }
            echo "\n";
        }
    }
    
    if (!$result['success']) {
        echo "\n❌ ERROR:\n";
        if (isset($result['error'])) {
            echo "   " . $result['error'] . "\n";
        }
        if (isset($result['data'])) {
            echo "   Datos: " . json_encode($result['data']) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>