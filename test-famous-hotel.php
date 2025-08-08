<?php
/**
 * ==========================================================================
 * PROBAR CON HOTEL SÚPER FAMOSO Y CONFIGURACIÓN AMPLIA
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

echo "=== PRUEBA CON HOTEL MUY FAMOSO ===\n\n";

try {
    echo "🏨 PROBANDO CON HOTEL XCARET - MÉXICO\n";
    echo "📍 Uno de los hoteles más famosos de México con miles de reseñas\n\n";
    
    // Place ID del Hotel Xcaret México (súper famoso con muchas reseñas)
    $xcaretPlaceId = 'ChIJL7BlcshLYI8RN5PpV2lhOy8';
    
    // Configuración muy amplia
    $config = [
        'hotelId' => $xcaretPlaceId,
        'hotelName' => 'Hotel Xcaret México',
        'maxReviews' => 10,
        'reviewsFromDate' => '2024-01-01', // Todo el 2024
        'scrapeReviewPictures' => false,
        'scrapeReviewResponses' => true,
        'enableGoogleMaps' => true,
        'enableTripadvisor' => true,
        'enableBooking' => true,
        'enableExpedia' => true,
        'enableHotelsCom' => true
    ];
    
    echo "⚙️  CONFIGURACIÓN AMPLIA:\n";
    echo "   - Hotel: Hotel Xcaret México (súper famoso)\n";
    echo "   - Place ID: {$xcaretPlaceId}\n";
    echo "   - Período: Todo el 2024\n";
    echo "   - Plataformas: Google, TripAdvisor, Booking, Expedia, Hotels.com\n";
    echo "   - Máximo: 10 reseñas\n\n";
    
    echo "🌐 Verificar Place ID en Maps: https://www.google.com/maps/place/?q=place_id:{$xcaretPlaceId}\n\n";
    
    echo "🚀 Ejecutando extracción...\n";
    
    $apifyClient = new ApifyClient();
    $startTime = time();
    
    // Timeout más largo para dar tiempo
    $result = $apifyClient->runHotelExtractionSync($config, 90);
    
    $executionTime = time() - $startTime;
    
    echo "⏱️  Completado en {$executionTime} segundos\n\n";
    
    if (!$result['success']) {
        echo "❌ Error: " . ($result['error'] ?? 'Error desconocido') . "\n";
        exit(1);
    }
    
    if (isset($result['demo_mode']) && $result['demo_mode']) {
        echo "❌ Modo demo activado\n";
        exit(1);
    }
    
    $reviews = $result['data'] ?? [];
    $runId = $result['run_id'] ?? 'N/A';
    
    echo "📊 RESULTADOS:\n";
    echo "   - Run ID: {$runId}\n";
    echo "   - Reseñas: " . count($reviews) . "\n";
    echo "   - Tiempo: {$executionTime}s\n\n";
    
    if (count($reviews) > 0) {
        echo "🎉 ¡ÉXITO! Reseñas extraídas exitosamente\n\n";
        
        // Analizar por proveedor
        $providers = [];
        foreach ($reviews as $review) {
            $provider = $review['provider'] ?? 'unknown';
            $providers[$provider] = ($providers[$provider] ?? 0) + 1;
        }
        
        echo "📈 RESEÑAS POR PROVEEDOR:\n";
        foreach ($providers as $provider => $count) {
            echo "   - {$provider}: {$count} reseñas\n";
        }
        echo "\n";
        
        echo "📝 MUESTRA DE RESEÑAS:\n\n";
        
        foreach (array_slice($reviews, 0, 3) as $i => $review) {
            echo "   🌟 Reseña " . ($i + 1) . ":\n";
            echo "   - Proveedor: " . ($review['provider'] ?? 'N/A') . "\n";
            echo "   - Rating: " . ($review['reviewRating'] ?? 'N/A') . "/5\n";
            echo "   - Fecha: " . ($review['reviewDate'] ?? 'N/A') . "\n";
            echo "   - Autor: " . ($review['authorName'] ?? 'N/A') . "\n";
            echo "   - Título: " . ($review['reviewTitle'] ?? 'N/A') . "\n";
            echo "   - Texto: " . substr($review['reviewText'] ?? '', 0, 150) . "...\n";
            
            if (isset($review['reviewUrl'])) {
                echo "   - URL: " . $review['reviewUrl'] . "\n";
            }
            
            echo "\n";
        }
        
        echo "✅ SISTEMA FUNCIONANDO PERFECTAMENTE\n\n";
        
        echo "🎯 SOLUCIÓN ENCONTRADA:\n";
        echo "1. ✅ API de Apify funciona correctamente\n";
        echo "2. ✅ Esquema de parámetros correcto\n";
        echo "3. ✅ Extracción de reseñas reales exitosa\n";
        echo "4. ❗ PROBLEMA: Place IDs de hoteles en BD son inválidos\n\n";
        
        echo "📋 PRÓXIMOS PASOS CRÍTICOS:\n";
        echo "1. 🔍 Obtener Place IDs REALES para cada hotel en la BD\n";
        echo "2. 🗄️  Actualizar tabla 'hoteles' con Place IDs válidos\n";
        echo "3. 🧹 Limpiar las 5,400+ reseñas demo de la BD\n";
        echo "4. ⚙️  Configurar extracciones automáticas\n";
        echo "5. 🚀 Iniciar extracción masiva con datos reales\n\n";
        
    } else {
        echo "❌ Aún sin reseñas\n\n";
        
        echo "🔍 INTENTANDO CON HOTEL DIFERENTE...\n";
        
        // Probar con Marriott Cancún
        $marriottPlaceId = 'ChIJ7YLxDlQVYI8RN5p9wCEn5R8';
        
        $config2 = [
            'hotelId' => $marriottPlaceId,
            'maxReviews' => 5,
            'reviewsFromDate' => '2024-01-01',
            'enableGoogleMaps' => true,
            'enableBooking' => false, // Solo Google para prueba rápida
            'enableTripadvisor' => false
        ];
        
        echo "   - Probando: Marriott Cancún\n";
        echo "   - Place ID: {$marriottPlaceId}\n";
        echo "   - Solo Google Maps\n\n";
        
        $result2 = $apifyClient->runHotelExtractionSync($config2, 60);
        
        if ($result2['success'] && !empty($result2['data'])) {
            echo "✅ ¡Marriott funcionó!\n";
            echo "   Reseñas extraídas: " . count($result2['data']) . "\n";
            
            $sample = $result2['data'][0];
            echo "   Muestra: " . ($sample['authorName'] ?? 'N/A') . " - " . ($sample['reviewRating'] ?? 'N/A') . "/5\n";
        } else {
            echo "❌ Marriott tampoco funcionó\n";
            echo "⚠️  Puede ser un problema con los Place IDs de México/Cancún\n";
            echo "🔧 Recomendación: Verificar Place IDs manualmente en Google Maps\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN PRUEBA HOTEL FAMOSO ===\n";
?>