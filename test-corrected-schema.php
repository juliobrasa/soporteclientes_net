<?php
/**
 * ==========================================================================
 * PROBAR CON ESQUEMA CORRECTO DEL HOTEL REVIEW AGGREGATOR
 * Usando la documentación oficial proporcionada
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

echo "=== PRUEBA CON ESQUEMA CORRECTO ===\n\n";

try {
    echo "🎯 USANDO ESQUEMA OFICIAL DEL HOTEL REVIEW AGGREGATOR\n";
    echo "📖 Basado en: https://apify.com/tri_angle/hotel-review-aggregator/api\n\n";
    
    // Place ID real del Hard Rock Cancún
    $realPlaceId = 'ChIJXcF3OJwYYI8RyKpI2yPHQ5U';
    
    // Configuración usando el esquema correcto
    $config = [
        'hotelId' => $realPlaceId,
        'hotelName' => 'Hard Rock Hotel Cancun',
        'maxReviews' => 5, // Solo 5 para prueba rápida
        'reviewsFromDate' => date('Y-m-d', strtotime('-60 days')), // Últimos 60 días
        'scrapeReviewPictures' => false,
        'scrapeReviewResponses' => true,
        'enableGoogleMaps' => true,
        'enableTripadvisor' => true,
        'enableBooking' => true
    ];
    
    echo "⚙️  CONFIGURACIÓN DE PRUEBA:\n";
    echo json_encode($config, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "🚀 Iniciando extracción con esquema correcto...\n";
    
    $apifyClient = new ApifyClient();
    $startTime = time();
    
    // Ejecutar con timeout de 60 segundos
    $result = $apifyClient->runHotelExtractionSync($config, 60);
    
    $executionTime = time() - $startTime;
    
    echo "⏱️  Extracción completada en {$executionTime} segundos\n\n";
    
    // Analizar resultados
    echo "📊 RESULTADOS:\n";
    
    if (!$result['success']) {
        echo "❌ Extracción falló: " . ($result['error'] ?? 'Error desconocido') . "\n";
        
        // Mostrar detalles del error para debug
        if (isset($result['debug'])) {
            echo "Debug info: " . print_r($result['debug'], true) . "\n";
        }
        
        exit(1);
    }
    
    // Verificar si es modo demo
    if (isset($result['demo_mode']) && $result['demo_mode']) {
        echo "❌ SIGUE EN MODO DEMO\n";
        echo "   El token no está funcionando correctamente\n\n";
        exit(1);
    }
    
    echo "✅ Extracción REAL ejecutada\n\n";
    
    $reviews = $result['data'] ?? [];
    $runId = $result['run_id'] ?? 'N/A';
    
    echo "   - Run ID: {$runId}\n";
    echo "   - Reseñas extraídas: " . count($reviews) . "\n";
    echo "   - Tiempo ejecución: {$executionTime} segundos\n\n";
    
    if (count($reviews) > 0) {
        echo "🎉 ¡ÉXITO! Sistema funcionando con esquema correcto\n\n";
        
        echo "📝 ESTRUCTURA DE LAS RESEÑAS EXTRAÍDAS:\n\n";
        
        foreach (array_slice($reviews, 0, 2) as $i => $review) {
            echo "   Reseña " . ($i + 1) . " (Formato correcto):\n";
            echo "   - Google Place ID: " . ($review['googleMapsPlaceId'] ?? 'N/A') . "\n";
            echo "   - Hotel: " . ($review['placeName'] ?? 'N/A') . "\n";
            echo "   - Dirección: " . ($review['placeAddress'] ?? 'N/A') . "\n";
            echo "   - Proveedor: " . ($review['provider'] ?? 'N/A') . "\n";
            echo "   - Review ID: " . ($review['reviewId'] ?? 'N/A') . "\n";
            echo "   - Título: " . ($review['reviewTitle'] ?? 'N/A') . "\n";
            echo "   - Texto: " . substr($review['reviewText'] ?? '', 0, 100) . "...\n";
            echo "   - Fecha: " . ($review['reviewDate'] ?? 'N/A') . "\n";
            echo "   - Rating: " . ($review['reviewRating'] ?? 'N/A') . "\n";
            echo "   - Autor: " . ($review['authorName'] ?? 'N/A') . "\n";
            echo "   - URL: " . ($review['reviewUrl'] ?? 'N/A') . "\n";
            
            if (isset($review['reviewResponses']) && !empty($review['reviewResponses'])) {
                echo "   - Respuestas: " . count($review['reviewResponses']) . " respuesta(s)\n";
            }
            
            echo "\n";
        }
        
        // Verificar que son datos reales
        $realReviews = 0;
        foreach ($reviews as $review) {
            if (!isset($review['reviewId']) || strpos($review['reviewId'], 'demo_') !== 0) {
                $realReviews++;
            }
        }
        
        echo "🔍 VERIFICACIÓN DE DATOS:\n";
        echo "   - Reseñas reales: {$realReviews}\n";
        echo "   - Reseñas demo: " . (count($reviews) - $realReviews) . "\n\n";
        
        if ($realReviews > 0) {
            echo "✅ ¡CONFIRMADO! Sistema extrayendo datos reales\n\n";
            
            echo "🎯 PRÓXIMOS PASOS:\n";
            echo "1. ✅ Esquema correcto identificado y funcionando\n";
            echo "2. ⭐ Obtener Place IDs reales para hoteles en base de datos\n";
            echo "3. 🧹 Limpiar reseñas demo existentes\n";
            echo "4. 🔄 Configurar extracciones automáticas\n";
            echo "5. 📊 Extraer reseñas de todos los hoteles\n\n";
            
        } else {
            echo "⚠️  Todas las reseñas son demo - verificar configuración\n";
        }
        
    } else {
        echo "❌ No se extrajeron reseñas\n\n";
        echo "🔧 POSIBLES CAUSAS:\n";
        echo "1. Place ID inválido o hotel sin reseñas recientes\n";
        echo "2. Período de fechas muy restringido\n";
        echo "3. Configuración de plataformas incorrecta\n";
        echo "4. Límites de API alcanzados\n\n";
        
        echo "💡 SOLUCIONES:\n";
        echo "- Probar con otro Place ID de hotel famoso\n";
        echo "- Ampliar rango de fechas (reviewsFromDate)\n";
        echo "- Verificar créditos en cuenta Apify\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR EN LA PRUEBA:\n";
    echo "   - " . $e->getMessage() . "\n\n";
    
    echo "🔧 VERIFICAR:\n";
    echo "1. Token Apify válido y con créditos\n";
    echo "2. Actor tri_angle/hotel-review-aggregator disponible\n";
    echo "3. Place ID correcto\n";
    echo "4. Conexión a internet estable\n";
}

echo "\n=== FIN PRUEBA ESQUEMA CORRECTO ===\n";
?>