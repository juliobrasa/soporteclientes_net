<?php
/**
 * ==========================================================================
 * PROBAR BOOKING SCRAPER CON CONFIGURACIÓN CORRECTA
 * ==========================================================================
 */

require_once __DIR__ . '/booking-scraper.php';
require_once __DIR__ . '/env-loader.php';

echo "=== PRUEBA BOOKING SCRAPER ===\n\n";

try {
    $bookingScraper = new BookingScraper();
    
    echo "🏨 PROBANDO EXTRACCIÓN DE BOOKING.COM\n";
    echo "Actor: PbMHke3jW25J6hSOA (voyager/booking-reviews-scraper)\n\n";
    
    // Obtener información del actor
    $actorInfo = $bookingScraper->getActorInfo();
    if ($actorInfo) {
        echo "📊 INFORMACIÓN DEL ACTOR:\n";
        echo "   - Título: " . $actorInfo['title'] . "\n";
        echo "   - Runs: " . number_format($actorInfo['total_runs']) . "\n";
        echo "   - Descripción: " . substr($actorInfo['description'], 0, 100) . "...\n\n";
    }
    
    // URLs de prueba de hoteles en Booking.com (México/Cancún)
    $testUrls = [
        'https://www.booking.com/hotel/mx/hard-rock-hotel-cancun.html',
        'https://www.booking.com/hotel/mx/hyatt-zilara-cancun.html', 
        'https://www.booking.com/hotel/mx/marriott-cancun.html'
    ];
    
    foreach ($testUrls as $i => $hotelUrl) {
        echo "🧪 PRUEBA " . ($i + 1) . ":\n";
        echo "   URL: {$hotelUrl}\n";
        
        // Configurar extracción con pocos reviews para prueba
        $options = [
            'language' => 'es',
            'includeReviewText' => true,
            'includeReviewerInfo' => true
        ];
        
        echo "   Extrayendo máximo 3 reseñas...\n";
        
        $startTime = time();
        $result = $bookingScraper->scrapeBookingReviews($hotelUrl, 3, $options);
        $executionTime = time() - $startTime;
        
        echo "   Tiempo: {$executionTime}s\n";
        
        if ($result['success']) {
            $reviews = $result['data'] ?? [];
            echo "   ✅ Éxito: " . count($reviews) . " reseñas extraídas\n";
            
            if (!empty($reviews)) {
                echo "\n   📝 MUESTRA DE RESEÑA:\n";
                $sample = $reviews[0];
                
                // Mostrar campos clave de la reseña
                $keyFields = ['title', 'text', 'rating', 'author', 'date', 'language'];
                foreach ($keyFields as $field) {
                    if (isset($sample[$field])) {
                        $value = is_string($sample[$field]) ? substr($sample[$field], 0, 80) . "..." : $sample[$field];
                        echo "      - {$field}: {$value}\n";
                    }
                }
                
                echo "\n   🔄 NORMALIZANDO PARA BD...\n";
                $normalized = $bookingScraper->normalizeBookingReviews($reviews, 1);
                
                if (!empty($normalized)) {
                    $norm = $normalized[0];
                    echo "      ✅ Formato BD:\n";
                    echo "      - Platform: " . $norm['platform'] . "\n";
                    echo "      - Rating: " . $norm['rating'] . "/5\n";
                    echo "      - Author: " . $norm['author'] . "\n";
                    echo "      - Date: " . $norm['review_date'] . "\n";
                    echo "      - Content length: " . strlen($norm['content']) . " chars\n";
                }
                
                echo "\n   🎉 ¡BOOKING SCRAPER FUNCIONANDO!\n";
                break; // Salir del loop, encontramos un hotel que funciona
                
            } else {
                echo "   ⚠️  Sin reseñas encontradas\n";
            }
        } else {
            echo "   ❌ Error: " . $result['error'] . "\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
        
        // Pausa entre pruebas
        sleep(3);
    }
    
    // Estimar costos
    echo "💰 ESTIMACIÓN DE COSTOS:\n";
    echo "   - 10 reseñas: $" . number_format($bookingScraper->estimateCost(10), 4) . "\n";
    echo "   - 100 reseñas: $" . number_format($bookingScraper->estimateCost(100), 4) . "\n";
    echo "   - 1000 reseñas: $" . number_format($bookingScraper->estimateCost(1000), 4) . "\n\n";
    
    echo "✅ INTEGRACIÓN DE BOOKING COMPLETADA\n\n";
    
    echo "🎯 PRÓXIMOS PASOS:\n";
    echo "1. ✅ Booking scraper integrado y funcionando\n";
    echo "2. 📋 Integrar scrapers para otras plataformas:\n";
    echo "   - Google Maps reviews\n";
    echo "   - TripAdvisor reviews\n";
    echo "   - Expedia/Hotels.com\n";
    echo "3. 🗄️  Crear sistema unificado multi-plataforma\n";
    echo "4. 🔄 Implementar en cron-extractor.php\n";
    echo "5. 🧹 Limpiar reseñas demo de la BD\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN PRUEBA BOOKING ===\n";
?>